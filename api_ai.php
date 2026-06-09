<?php
require_once 'auth.php';
check_auth();

header('Content-Type: application/json');

$config_file = __DIR__ . '/data/ai_config.json';
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

function get_ai_config() {
    global $config_file;
    if (file_exists($config_file)) {
        return json_decode(file_get_contents($config_file), true);
    }
    return ['api_key' => '', 'selected_model' => ''];
}

function save_ai_config($data) {
    global $config_file;
    file_put_contents($config_file, json_encode($data, JSON_PRETTY_PRINT));
}

function clean_ai_response($text) {
    $text = trim($text);
    
    // 1. Enlever les blocs de code Markdown (e.g. ```text ... ``` ou ``` ... ```)
    if (preg_match('/^```(?:[a-zA-Z0-9_-]+)?\s*(.*?)\s*```$/us', $text, $matches)) {
        $text = $matches[1];
    }
    
    $strip_quotes = function($t) {
        $t = trim($t);
        $changed = true;
        while ($changed) {
            $changed = false;
            $len = mb_strlen($t);
            if ($len < 2) {
                break;
            }
            
            $first = mb_substr($t, 0, 1);
            $last = mb_substr($t, -1, 1);
            
            $matching_quotes = [
                ['"', '"'],
                ["'", "'"],
                ['«', '»'],
                ['“', '”'],
                ['‘', '’'],
                ['„', '“'],
            ];
            
            foreach ($matching_quotes as $pair) {
                if ($first === $pair[0] && $last === $pair[1]) {
                    $t = mb_substr($t, 1, -1);
                    $t = trim($t);
                    $changed = true;
                    break;
                }
            }
        }
        return trim($t);
    };

    $strip_greetings = function($t) {
        $patterns = [
            '/^(bonjour|hello|salut)\b[^\n]*\n+/i',
            '/^voici\s+(le\s+|la\s+|une\s+|votre\s+|ce\s+que\s+je\s+propose\s+)?(texte|titre|résumé|post|description|proposition|méta-description|content|contenu)\s*(?:demandé|pour\s+[^\n:]+)?\s*(?::|—|-|\n)+\s*/i',
            '/^(here\s+is|here\s+are|sure,\s+here\s+is)\s+(the\s+|a\s+|your\s+)?(text|title|summary|post|description|meta-description|content)\s*(?::|—|-|\n)+\s*/i',
            '/^(bien\s+sûr|certainement|volontiers)\s*,\s*voici\s+.*\s*(?::|—|-|\n)+\s*/i',
            '/^sure\s*,\s*here\s+.*\s*(?::|—|-|\n)+\s*/i',
            '/^en\s+tant\s+qu\'expert\s+.*\s*(?::|—|-|\n)+\s*/i',
        ];
        
        $prev = '';
        while ($prev !== $t) {
            $prev = $t;
            foreach ($patterns as $pattern) {
                $t = preg_replace($pattern, '', $t);
            }
            $t = trim($t);
        }
        return $t;
    };
    
    // Nettoyer les guillemets et préambules alternativement
    for ($i = 0; $i < 3; $i++) {
        $original = $text;
        $text = $strip_quotes($text);
        $text = $strip_greetings($text);
        if ($text === $original) {
            break;
        }
    }
    
    // Deuxième passe pour les blocs markdown cachés
    if (preg_match('/^```(?:[a-zA-Z0-9_-]+)?\s*(.*?)\s*```$/us', $text, $matches)) {
        $text = $matches[1];
    }
    
    return trim($text);
}

$action = $_GET['action'] ?? '';
$input_data = json_decode(file_get_contents('php://input'), true) ?? [];

// 1. Sauvegarde des réglages
if ($action === 'save_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = get_ai_config();
    if (isset($input_data['api_key'])) $config['api_key'] = trim($input_data['api_key']);
    if (isset($input_data['selected_model'])) $config['selected_model'] = $input_data['selected_model'];
    save_ai_config($config);
    echo json_encode(['status' => 'success', 'config' => $config]);
    exit;
}

// 2. Fetch des modèles
if ($action === 'fetch_models') {
    $config = get_ai_config();
    $api_key = $_GET['api_key'] ?? $config['api_key'];
    if (empty($api_key)) {
        echo json_encode(['status' => 'error', 'message' => 'Clé API manquante']);
        exit;
    }
    $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $api_key;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code !== 200) {
        echo json_encode(['status' => 'error', 'message' => 'Erreur API (' . $http_code . ')', 'details' => json_decode($response)]);
        exit;
    }
    $data = json_decode($response, true);
    $models = $data['models'] ?? [];
    $formatted_models = [];
    foreach ($models as $m) {
        if (isset($m['supportedGenerationMethods']) && in_array('generateContent', $m['supportedGenerationMethods'])) {
            $id = str_replace('models/', '', $m['name']);
            $role = "Usage Général";
            $weight = "Normal";
            if (stripos($id, 'pro') !== false) { $role = "Expert / Complexe"; $weight = "Lourd (Puissance)"; } 
            elseif (stripos($id, 'flash-lite') !== false) { $role = "Ultra-rapide / Lite"; $weight = "Plume (Éco)"; }
            elseif (stripos($id, 'flash') !== false) { $role = "Rapide / Support"; $weight = "Léger (Vitesse)"; }
            elseif (stripos($id, 'vision') !== false) { $role = "Analyse Image"; $weight = "Moyen"; }
            $formatted_models[] = [
                'id' => $id, 'displayName' => $m['displayName'], 'description' => $m['description'],
                'role' => $role, 'weight' => $weight, 'inputTokenLimit' => $m['inputTokenLimit'] ?? '?'
            ];
        }
    }
    echo json_encode(['status' => 'success', 'models' => $formatted_models]);
    exit;
}

// 3. Génération de texte
if ($action === 'generate') {
    $config = get_ai_config();
    if (empty($config['api_key']) || empty($config['selected_model'])) {
        echo json_encode(['status' => 'error', 'message' => 'Configuration IA incomplète']);
        exit;
    }
    $prompt_input = $input_data['prompt'] ?? ($_GET['prompt'] ?? '');
    $field = $input_data['field'] ?? ($_GET['field'] ?? 'general');
    if (empty($prompt_input)) {
        echo json_encode(['status' => 'error', 'message' => 'Rien à transformer.']);
        exit;
    }
    $system_instructions = "Tu es l'assistant personnel d'un créatif (designer/développeur haut de gamme). Ton but est d'aider à rédiger les textes d'un portfolio. IMPORTANT : Ne sois PAS un vendeur. Évite le langage marketing cliché. Adopte un ton authentique, sobre, technique mais accessible. CONSIGNE CRITIQUE : Réponds UNIQUEMENT avec le texte final brut demandé. Ne fais JAMAIS de phrases d'introduction (par exemple, pas de \"Voici le texte\", pas de \"Bonjour\"), pas de conclusion, n'utilise pas d'enrobage Markdown (pas de ```text ni de ```), et ne l'entoure pas de guillemets. Donne le texte brut direct prêt à être copié-collé. Consignes spécifiques selon la section :";
    if ($field === 'brief') {
        $system_instructions .= "\n- Section BRIEF : Décris de façon concise et structurée la consigne de départ, la demande du client ou de l'enseignant, ainsi que les objectifs initiaux et contraintes imposées.";
    } elseif ($field === 'concept') {
        $system_instructions .= "\n- Section CONCEPT : Focalise-toi sur l'idée, l'origine, l'esthétique et la vision du projet. Pourquoi ce projet existe-t-il ? Quelle est l'âme du projet ?";
    } elseif ($field === 'defis') {
        $system_instructions .= "\n- Section DÉFIS : Sois honnête sur les difficultés rencontrées (techniques ou design) et raconte comment elles ont été surmontées avec ingéniosité.";
    } elseif ($field === 'resultats') {
        $system_instructions .= "\n- Section RÉSULTATS : Parle de ce que le projet a apporté, de sa finalité concrète et de l'apprentissage personnel, sans en faire trop.";
    } elseif ($field === 'seo_title') {
        $system_instructions = "Tu es un expert en SEO pour créatifs. Génère un titre de page (max 60 caractères) extrêmement accrocheur et pro pour le projet suivant. Utilise des mots-clés pertinents sans être 'spammy'. Réponds uniquement par le titre.";
    } elseif ($field === 'seo_description') {
        $system_instructions = "Tu es un spécialiste du SEO. Rédige une méta-description (max 155 caractères) qui donne envie de cliquer. Elle doit résumer le projet de manière élégante et pro. Réponds uniquement par la description.";
    } elseif ($field === 'social_post') {
        $platform = $input_data['platform'] ?? 'LinkedIn';
        $system_instructions = "Tu es un social media manager spécialisé dans le design. Rédige un post $platform captivant pour présenter ce projet. 
        - Ton : Inspirant, authentique, humble mais fier.
        - Structure : Accroche forte, un court paragraphe sur la vision, et 3-5 hashtags pertinents.
        - Pas d'emojis excessifs.
        Réponds uniquement par le contenu du post.";
    }

    $prompt = $system_instructions . "\n\nVoici les informations du projet :\n\"" . $prompt_input . "\"\n\nCONSIGNE FINALE ABSOLUE : Rédige uniquement le texte demandé. Ne dis rien d'autre. Pas d'introduction, pas de salutation, pas d'explication, aucun enrobage, aucun guillemet.";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $config['selected_model'] . ":generateContent?key=" . $config['api_key'];
    $payload = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1024],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE']
        ]
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code !== 200) {
        echo json_encode(['status' => 'error', 'message' => 'Erreur Gemini API', 'details' => json_decode($response)]);
        exit;
    }
    $result = json_decode($response, true);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Erreur de génération';
    echo json_encode(['status' => 'success', 'generated_text' => clean_ai_response($text)]);
    exit;
}

// 4. Get Config
if ($action === 'get_config') {
    echo json_encode(get_ai_config());
    exit;
}
?>

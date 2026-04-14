<?php
// ================================================
// ALTERNATIVE CONFIGURATION - FASTER MODEL
// Use this if NVIDIA Nemotron is too slow
// ================================================

// OPTION 1: Qwen2.5-VL-3B (Very Fast, Free, Reliable)
// Uncomment these lines to use:
/*
define('GEMINI_API_KEY', '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');
define('GEMINI_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
define('AI_MODEL', 'qwen/qwen-2.5-vl-3b-instruct:free');
define('API_TIMEOUT', 30);
*/

// OPTION 2: Google Gemini Flash 1.5 via OpenRouter (Fast, Free)
// Uncomment these lines to use:
/*
define('GEMINI_API_KEY', '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');
define('GEMINI_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
define('AI_MODEL', 'google/gemini-flash-1.5');
define('API_TIMEOUT', 20);
*/

// OPTION 3: GLM-4.6V-Flash (Fast, Free, Good for Vision)
// Uncomment these lines to use:
/*
define('GEMINI_API_KEY', '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');
define('GEMINI_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
define('AI_MODEL', 'zhipuai/glm-4-6v-flash:free');
define('API_TIMEOUT', 25);
*/

// ================================================
// HOW TO USE:
// ================================================
// 1. Choose one option above
// 2. Uncomment the 4 lines (remove /* and */)
// 3. Comment out the current config in vision_config.php
// 4. Save and test
// ================================================

?>

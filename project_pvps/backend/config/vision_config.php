<?php
// ================================================
// OpenRouter NVIDIA Nemotron Nano - Palm Vein Recognition Configuration
// Advanced AI-Powered Biometric Authentication System
// ================================================

// OpenRouter API Key (unlimited free tier with vision support)
define('GEMINI_API_KEY', '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');

// OpenRouter API Endpoint (using NVIDIA Nemotron Nano 12B 2 VL model)
// This model supports multimodal vision analysis with unlimited free access
define('GEMINI_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');

// Model identifier for OpenRouter (correct format: v2 not 2)
define('AI_MODEL', 'nvidia/nemotron-nano-12b-v2-vl:free');

// Legacy Vision API (fallback - not used with OpenRouter)
define('VISION_API_ENDPOINT', 'https://vision.googleapis.com/v1/images:annotate');

// Vision API Key (placeholder - not actively used, kept for compatibility)
define('VISION_API_KEY', 'YOUR_GOOGLE_VISION_API_KEY_HERE');

// Enable/disable legacy Vision API validation (DISABLED - using OpenRouter instead)
define('ENABLE_VISION_VALIDATION', false);

// Hand detection threshold for legacy Vision API (0.0 to 1.0)
define('HAND_DETECTION_THRESHOLD', 0.70);

// Hand-related labels for legacy Vision API detection
define('HAND_LABELS', [
    'hand',
    'palm',
    'finger',
    'thumb',
    'wrist',
    'fist',
    'gesture'
]);

// ================================================
// PALM DETECTION CONFIGURATION
// ================================================

// Primary detection threshold (0.0 to 1.0)
// Higher values = stricter validation
define('PALM_DETECTION_THRESHOLD', 0.75);

// Minimum confidence for palm vein pattern recognition
define('VEIN_PATTERN_THRESHOLD', 0.65);

// Matching threshold for authentication (0.0 to 1.0)
// How similar two palms must be to be considered a match
define('PALM_MATCH_THRESHOLD', 0.82);

// ================================================
// STRICT VALIDATION RULES
// ================================================

// Keywords that MUST be present for valid palm image
define('REQUIRED_PALM_KEYWORDS', [
    'hand',
    'palm',
    'finger',
    'skin'
]);

// Keywords that indicate INVALID images (reject these)
define('REJECTED_KEYWORDS', [
    'face',
    'person',
    'people',
    'animal',
    'cat',
    'dog',
    'bird',
    'vehicle',
    'car',
    'building',
    'landscape',
    'food',
    'text',
    'document',
    'screen',
    'monitor'
]);

// Minimum number of fingers that should be visible
define('MIN_FINGERS_VISIBLE', 3);

// Maximum number of hands allowed (should be 1 for security)
define('MAX_HANDS_ALLOWED', 1);

// ================================================
// DEEP LEARNING ANALYSIS FEATURES
// ================================================

// Features to extract from palm image
define('PALM_ANALYSIS_FEATURES', [
    'vein_pattern',      // Vein structure and pattern
    'palm_lines',        // Major palm lines (heart, head, life)
    'skin_texture',      // Skin texture and quality
    'finger_geometry',   // Finger length and proportions
    'palm_shape',        // Overall palm shape
    'color_analysis',    // Skin tone and color distribution
    'edge_detection',    // Edge patterns for vein detection
    'contrast_levels'    // Image quality metrics
]);

// ================================================
// IMAGE QUALITY REQUIREMENTS
// ================================================

// Minimum image dimensions (pixels)
define('MIN_IMAGE_WIDTH', 320);
define('MIN_IMAGE_HEIGHT', 240);

// Maximum image size (bytes) - 5MB
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);

// Required image formats
define('ALLOWED_IMAGE_FORMATS', ['image/jpeg', 'image/png', 'image/webp']);

// ================================================
// SECURITY & PRIVACY SETTINGS
// ================================================

// Enable/disable palm validation (ALWAYS ENABLED FOR SECURITY)
// WARNING: Do NOT disable this in production!
define('ENABLE_PALM_VALIDATION', true);

// Enable deep learning analysis (ALWAYS ENABLED)
define('ENABLE_DEEP_ANALYSIS', true);

// Enable palm matching for authentication (ALWAYS ENABLED)
define('ENABLE_PALM_MATCHING', true);

// Store original palm images (for debugging - disable in production)
define('STORE_ORIGINAL_IMAGES', true);

// Image storage directory (relative to backend folder)
define('PALM_IMAGE_DIRECTORY', '../palm_images/');

// Enable analytics and reporting
define('ENABLE_ANALYTICS', true);

// ================================================
// RATE LIMITING & PERFORMANCE
// ================================================

// Maximum API requests per user per minute
define('MAX_API_REQUESTS_PER_MINUTE', 10);

// Cache duration for palm analysis (seconds)
define('ANALYSIS_CACHE_DURATION', 300); // 5 minutes

// API timeout (seconds) - increased for OpenRouter vision processing
define('API_TIMEOUT', 45);

// ================================================
// GEMINI PROMPT CONFIGURATION
// ================================================

// System prompt for comprehensive hand analysis
define('PALM_ANALYSIS_PROMPT', 
    "You are an expert biometric authentication system specializing in comprehensive hand recognition. " .
    "Analyze the provided image with extreme precision and provide a detailed assessment.\n\n" .
    "CRITICAL INSTRUCTIONS:\n" .
    "1. FIRST, identify ALL objects visible in the image\n" .
    "2. If you detect ANY objects other than a human hand (e.g., face, person, animal, vehicle, furniture, electronics, food, etc.), list them in 'detected_objects' array\n" .
    "3. The image MUST show ONLY a human hand with clearly visible fingers\n" .
    "4. REJECT if multiple hands, faces, animals, objects, or non-hand content is detected\n\n" .
    "STRICT HAND REQUIREMENTS:\n" .
    "- Must be a single human hand (left or right)\n" .
    "- Fingers must be clearly visible and spread open\n" .
    "- At least 3-5 fingers should be visible\n" .
    "- Hand must be well-lit with clear details\n" .
    "- No gloves, jewelry, or obstructions on the hand\n\n" .
    "COMPREHENSIVE HAND ANALYSIS:\n" .
    "1. FINGER ANALYSIS:\n" .
    "   - Count exact number of visible fingers\n" .
    "   - Measure finger lengths (thumb, index, middle, ring, pinky)\n" .
    "   - Analyze finger spacing and gaps\n" .
    "   - Detect finger joints and knuckles\n" .
    "   - Evaluate finger curvature and flexibility\n" .
    "2. PALM PATTERN ANALYSIS:\n" .
    "   - Detect palm lines (heart line, head line, life line, fate line)\n" .
    "   - Analyze palm crease patterns and depth\n" .
    "   - Identify palm mounts (Jupiter, Saturn, Apollo, Mercury, Venus, Luna)\n" .
    "3. SKIN TEXTURE ANALYSIS:\n" .
    "   - Evaluate skin texture quality and smoothness\n" .
    "   - Detect skin tone and color distribution\n" .
    "   - Analyze pore visibility and skin patterns\n" .
    "4. HAND GEOMETRY:\n" .
    "   - Measure palm width and height\n" .
    "   - Calculate hand aspect ratio\n" .
    "   - Analyze overall hand shape (square, rectangular, conical, spatulate)\n" .
    "5. VEIN PATTERN (if visible):\n" .
    "   - Detect visible vein structures\n" .
    "   - Map vein branching patterns\n" .
    "6. IMAGE QUALITY:\n" .
    "   - Assess lighting conditions\n" .
    "   - Evaluate focus and sharpness\n" .
    "   - Check for blur or motion artifacts\n\n" .
    "RESPONSE FORMAT (JSON):\n" .
    "{\n" .
    "  \"is_valid_hand\": true/false,\n" .
    "  \"confidence\": 0.0-1.0,\n" .
    "  \"detected_objects\": [\"list all objects detected\"],\n" .
    "  \"rejection_reason\": \"specific reason if rejected\",\n" .
    "  \"suggestion\": \"helpful suggestion for user if rejected\",\n" .
    "  \"finger_analysis\": {\n" .
    "    \"finger_count\": 5,\n" .
    "    \"fingers_visible\": [\"thumb\", \"index\", \"middle\", \"ring\", \"pinky\"],\n" .
    "    \"finger_lengths\": {\"thumb\": \"short/medium/long\", ...},\n" .
    "    \"finger_spacing\": \"narrow/medium/wide\",\n" .
    "    \"joint_visibility\": \"clear/moderate/poor\"\n" .
    "  },\n" .
    "  \"palm_patterns\": {\n" .
    "    \"heart_line\": \"detected/not_detected\",\n" .
    "    \"head_line\": \"detected/not_detected\",\n" .
    "    \"life_line\": \"detected/not_detected\",\n" .
    "    \"palm_creases\": \"deep/moderate/shallow\"\n" .
    "  },\n" .
    "  \"skin_texture\": {\n" .
    "    \"quality\": \"excellent/good/fair/poor\",\n" .
    "    \"smoothness\": 0.0-1.0,\n" .
    "    \"skin_tone\": \"description\"\n" .
    "  },\n" .
    "  \"hand_geometry\": {\n" .
    "    \"palm_width\": \"pixels or relative\",\n" .
    "    \"palm_height\": \"pixels or relative\",\n" .
    "    \"hand_shape\": \"square/rectangular/conical/spatulate\"\n" .
    "  },\n" .
    "  \"vein_pattern\": {\n" .
    "    \"visible\": true/false,\n" .
    "    \"pattern_quality\": \"excellent/good/fair/poor\"\n" .
    "  },\n" .
    "  \"image_quality\": {\n" .
    "    \"lighting\": \"excellent/good/fair/poor\",\n" .
    "    \"focus\": \"sharp/moderate/blurry\",\n" .
    "    \"overall_quality\": \"excellent/good/fair/poor\"\n" .
    "  },\n" .
    "  \"biometric_signature\": \"unique hash based on hand features\"\n" .
    "}\n\n" .
    "Be extremely precise and thorough in your analysis. If the image does not show a clear human hand, set is_valid_hand to false and provide specific detected_objects and helpful suggestions."
);

// Prompt for hand matching
define('PALM_MATCHING_PROMPT',
    "You are a biometric matching expert specializing in comprehensive hand recognition. " .
    "Compare these two hand images and determine if they belong to the same person.\n\n" .
    "COMPREHENSIVE MATCHING CRITERIA:\n" .
    "1. FINGER MATCHING (40% weight):\n" .
    "   - Finger length proportions\n" .
    "   - Finger spacing patterns\n" .
    "   - Joint structure similarity\n" .
    "   - Finger curvature matching\n" .
    "2. PALM PATTERN MATCHING (30% weight):\n" .
    "   - Palm line similarity (heart, head, life lines)\n" .
    "   - Crease pattern matching\n" .
    "   - Palm mount positions\n" .
    "3. SKIN TEXTURE MATCHING (15% weight):\n" .
    "   - Texture pattern similarity\n" .
    "   - Skin tone consistency\n" .
    "   - Pore pattern matching\n" .
    "4. HAND GEOMETRY MATCHING (10% weight):\n" .
    "   - Palm width/height ratio\n" .
    "   - Overall hand shape\n" .
    "   - Aspect ratio comparison\n" .
    "5. VEIN PATTERN MATCHING (5% weight, if visible):\n" .
    "   - Vein structure similarity\n" .
    "   - Branching pattern matching\n\n" .
    "RESPONSE FORMAT (JSON):\n" .
    "{\n" .
    "  \"is_match\": true/false,\n" .
    "  \"match_score\": 0.0-1.0,\n" .
    "  \"confidence\": 0.0-1.0,\n" .
    "  \"match_details\": {\n" .
    "    \"finger_match\": 0.0-1.0,\n" .
    "    \"palm_pattern_match\": 0.0-1.0,\n" .
    "    \"skin_texture_match\": 0.0-1.0,\n" .
    "    \"hand_geometry_match\": 0.0-1.0,\n" .
    "    \"vein_pattern_match\": 0.0-1.0\n" .
    "  },\n" .
    "  \"overall_similarity\": \"percentage\",\n" .
    "  \"matching_features\": [\"list of matching features\"],\n" .
    "  \"differing_features\": [\"list of different features\"]\n" .
    "}\n\n" .
    "Provide a detailed match score (0-100) with comprehensive comparison metrics."
);

?>

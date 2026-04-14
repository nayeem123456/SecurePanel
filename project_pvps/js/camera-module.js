// ================================================
// Camera Module - REAL Palm Validation with Gemini AI
// Complete rewrite with strict validation
// ================================================

/**
 * Initialize palm scanning with REAL validation
 * @param {Object} options - Configuration
 * @param {Function} onComplete - Callback(success, capturedImage, analysisResult)
 */
async function initPalmScanModal(options, onComplete) {
    const modal = document.getElementById('palmScanModal');
    const video = document.getElementById('palmScanVideo');
    const statusText = document.getElementById('palmScanStatusText');
    const titleEl = document.getElementById('palmScanTitle');
    const subtitleEl = document.getElementById('palmScanSubtitle');
    const timerEl = document.getElementById('palmScanTimer');

    if (!modal || !video) {
        console.error('Palm scan modal elements not found');
        if (onComplete) onComplete(false, null, { message: 'Modal not found' });
        return;
    }

    // Set custom text
    if (options.title && titleEl) titleEl.textContent = options.title;
    if (options.subtitle && subtitleEl) subtitleEl.textContent = options.subtitle;
    if (options.statusMessage && statusText) statusText.textContent = options.statusMessage;

    // Show modal
    modal.classList.add('active');
    document.body.classList.add('modal-open');

    // Start REAL palm scanning with validation
    await initPalmScan(video, (status) => {
        if (statusText) statusText.textContent = status;
    }, (success, capturedImage, analysisResult) => {
        if (success) {
            // Real validation passed!
            if (timerEl) timerEl.style.display = 'none';
            if (statusText) statusText.textContent = '✓ Palm verified - ' + Math.round(analysisResult.confidence * 100) + '% confidence';

            // Close after showing result
            setTimeout(() => {
                modal.classList.remove('active');
                document.body.classList.remove('modal-open');
                if (onComplete) onComplete(true, capturedImage, analysisResult);
            }, 1500);
        } else {
            // Validation failed
            if (timerEl) timerEl.style.display = 'none';
            if (statusText) statusText.textContent = '❌ ' + (analysisResult?.message || 'Validation failed');
            
            // Close after showing error
            setTimeout(() => {
                modal.classList.remove('active');
                document.body.classList.remove('modal-open');
                if (onComplete) onComplete(false, null, analysisResult);
            }, 3000);
        }
    });
}

/**
 * REAL Palm Scan with Gemini AI Validation
 */
async function initPalmScan(videoElement, onStatusUpdate, onComplete) {
    let stream = null;
    let capturedImageData = null;

    try {
        // Step 1: Request camera access
        if (onStatusUpdate) onStatusUpdate('Requesting camera access...');

        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        });

        // Step 2: Display video feed
        videoElement.srcObject = stream;
        videoElement.classList.add('active');
        await videoElement.play();

        if (onStatusUpdate) onStatusUpdate('Position your palm in front of camera...');

        // Wait 2 seconds for user to position palm
        await new Promise(resolve => setTimeout(resolve, 2000));

        // Step 3: Capture frame
        if (onStatusUpdate) onStatusUpdate('Capturing palm image...');
        capturedImageData = captureFrameAsBase64(videoElement);

        if (!capturedImageData) {
            throw new Error('Failed to capture image');
        }

        // Step 4: Validate with Gemini AI
        if (onStatusUpdate) onStatusUpdate('Validating with AI... Please wait...');

        const validationResult = await validatePalmWithGemini(capturedImageData);

        // Step 5: Stop camera
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        videoElement.srcObject = null;
        videoElement.classList.remove('active');

        // Step 6: Return result
        if (validationResult.success && validationResult.isPalm) {
            // VALID PALM!
            if (onComplete) onComplete(true, capturedImageData, validationResult);
        } else {
            // INVALID - Not a palm or validation failed
            if (onComplete) onComplete(false, null, validationResult);
        }

    } catch (error) {
        console.error('Palm scan error:', error);

        // Stop camera
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        videoElement.srcObject = null;
        videoElement.classList.remove('active');

        // Report error
        let errorMsg = 'Camera error: ' + error.message;
        if (error.name === 'NotAllowedError') errorMsg = 'Camera access denied. Please allow camera access.';
        else if (error.name === 'NotFoundError') errorMsg = 'No camera found. Please connect a camera.';

        if (onStatusUpdate) onStatusUpdate(errorMsg);
        if (onComplete) onComplete(false, null, { success: false, message: errorMsg });
    }
}

/**
 * Validate palm image with Gemini AI
 * Calls the backend API for real validation
 */
async function validatePalmWithGemini(imageBase64) {
    try {
        const response = await fetch('backend/api/palm_recognition.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'analyze',
                palm_image_data: imageBase64
            })
        });

        if (!response.ok) {
            throw new Error('API request failed: ' + response.status);
        }

        const result = await response.json();
        
        // The API returns the analysis directly from analyzePalmWithGemini()
        return result;

    } catch (error) {
        console.error('Gemini validation error:', error);
        return {
            success: false,
            isPalm: false,
            confidence: 0,
            message: 'Validation error: ' + error.message
        };
    }
}

/**
 * Capture current video frame as base64
 */
function captureFrameAsBase64(videoElement) {
    const canvas = document.createElement('canvas');
    canvas.width = videoElement.videoWidth;
    canvas.height = videoElement.videoHeight;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

    return canvas.toDataURL('image/jpeg', 0.85);
}

/**
 * Check if camera is available
 */
function isCameraAvailable() {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
}

/**
 * Show payment success screen
 */
function showPaymentSuccess(amount, transactionId) {
    const modal = document.getElementById('palmScanModal');
    const videoContainer = modal.querySelector('.palm-scan-video-container');
    const footer = modal.querySelector('.palm-scan-modal-footer');

    if (!modal || !videoContainer) return;

    if (footer) footer.style.display = 'none';

    const originalContent = videoContainer.innerHTML;

    videoContainer.innerHTML = `
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); border-radius: var(--radius-md);">
            <div style="font-size: 80px; color: white;">✓</div>
            <h2 style="color: white; margin: 1rem 0; text-align: center;">Payment Authorized</h2>
            <p style="color: white; font-weight: bold; font-size: 1.2rem;">${amount}</p>
            <p style="color: white; font-size: 0.9rem; margin-top: 0.5rem;">Transaction ID: ${transactionId}</p>
        </div>
    `;

    return new Promise((resolve) => {
        setTimeout(() => {
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');

            if (footer) footer.style.display = '';
            videoContainer.innerHTML = originalContent;

            resolve();
        }, 3000);
    });
}

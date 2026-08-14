/**
 * Compress an image file using HTML5 Canvas / createImageBitmap.
 * 
 * @param {File} file - The raw image file object.
 * @param {number} maxWidth - Maximum width boundary (default 1024).
 * @param {number} maxHeight - Maximum height boundary (default 1024).
 * @param {number} quality - Compression quality (0.0 to 1.0, default 0.6).
 * @returns {Promise<string>} Resolves with the compressed base64 data URL.
 */
export const compressImage = (file, maxWidth = 800, maxHeight = 800, quality = 0.5) => {
    return new Promise(async (resolve, reject) => {
        if (!file || !(file instanceof File || file instanceof Blob)) {
            return reject(new Error('Invalid file object provided for compression.'));
        }

        // Try createImageBitmap first if available (faster & memory efficient on mobile devices)
        if (typeof window.createImageBitmap === 'function') {
            try {
                const bitmap = await window.createImageBitmap(file);
                let width = bitmap.width;
                let height = bitmap.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                if (ctx) {
                    ctx.drawImage(bitmap, 0, 0, width, height);
                    const dataUrl = canvas.toDataURL('image/jpeg', quality);
                    bitmap.close();
                    if (dataUrl && dataUrl.startsWith('data:image/')) {
                        return resolve(dataUrl);
                    }
                }
            } catch (err) {
                // Fallback to Image element method below if createImageBitmap fails (e.g. SVG or HEIC)
            }
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new window.Image();
            img.crossOrigin = 'anonymous';
            img.src = event.target.result;
            img.onload = () => {
                try {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    if (width > height) {
                        if (width > maxWidth) {
                            height = Math.round((height * maxWidth) / width);
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width = Math.round((width * maxHeight) / height);
                            height = maxHeight;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    const dataUrl = canvas.toDataURL('image/jpeg', quality);
                    resolve(dataUrl);
                } catch (err) {
                    reject(err);
                }
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};


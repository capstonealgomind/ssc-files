import { ref } from 'vue';

export function usePdfDownload() {
    const downloading = ref(false);

    async function downloadPdf(url, filename = 'ballot-receipt.pdf') {
        if (!url || downloading.value) {
            return;
        }

        downloading.value = true;

        try {
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/pdf',
                },
            });

            if (!response.ok) {
                throw new Error(`Download failed (${response.status})`);
            }

            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('text/html')) {
                throw new Error('Server returned HTML instead of PDF');
            }

            const blob = await response.blob();
            const pdfBlob = blob.type === 'application/pdf'
                ? blob
                : new Blob([blob], { type: 'application/pdf' });

            const blobUrl = URL.createObjectURL(pdfBlob);
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
        } catch {
            // Signed URLs work without session cookies when opened directly (mobile download managers).
            window.location.assign(url);
        } finally {
            downloading.value = false;
        }
    }

    return { downloadPdf, downloading };
}

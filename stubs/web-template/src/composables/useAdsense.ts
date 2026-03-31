import { onMounted } from 'vue';
import { useConfig } from './useConfig';

export function useAdsense() {
    const config = useConfig();

    onMounted(() => {
        const adsenseId = config.ads?.adsense_id;
        if (!adsenseId) return;

        // Inject AdSense script
        const script = document.createElement('script');
        script.async = true;
        script.crossOrigin = 'anonymous';
        script.src = `https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${adsenseId}`;
        document.head.appendChild(script);
    });
}

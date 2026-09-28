import { onBeforeUnmount } from 'vue';

export function useSpeech() {
    function speak(text: string, lang = 'en-US'): void {
        if (typeof window === 'undefined' || !('speechSynthesis' in window)) {
            return;
        }

        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = lang;
        utterance.rate = 0.95;
        window.speechSynthesis.speak(utterance);
    }

    function stop(): void {
        if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
    }

    onBeforeUnmount(() => stop());

    return { speak, stop };
}

export function languageCodeToBcp47(code: string | null | undefined): string {
    if (!code) {
        return 'en-US';
    }

    const map: Record<string, string> = {
        en: 'en-US',
        de: 'de-DE',
        es: 'es-ES',
        fr: 'fr-FR',
        it: 'it-IT',
        ru: 'ru-RU',
        pt: 'pt-PT',
        ja: 'ja-JP',
        zh: 'zh-CN',
    };

    return map[code.toLowerCase()] ?? code;
}

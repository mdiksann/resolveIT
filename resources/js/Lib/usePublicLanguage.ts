import { useEffect, useState } from 'react';

export type PublicLanguage = 'en' | 'id';

export function usePublicLanguage() {
  const [language, setLanguage] = useState<PublicLanguage>(() => {
    try {
      return localStorage.getItem('resolveit-language') === 'en' ? 'en' : 'id';
    } catch {
      return 'id';
    }
  });

  useEffect(() => {
    const previous = document.documentElement.lang;
    document.documentElement.lang = language;
    try {
      localStorage.setItem('resolveit-language', language);
    } catch {
      // The selector still works when browser storage is unavailable.
    }
    return () => {
      document.documentElement.lang = previous;
    };
  }, [language]);

  const t = (english: string, indonesian: string) => (language === 'en' ? english : indonesian);
  return { language, setLanguage, t };
}

import { ref } from 'vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'

export function useMarkdown() {
    const md = new MarkdownIt({
        html: true,
        linkify: true,
        typographer: true,
        highlight: function (str, lang) {
            if (lang && hljs.getLanguage(lang)) {
                try {
                    return '<pre class="hljs"><code>' +
                           hljs.highlight(str, { language: lang, ignoreIllegals: true }).value +
                           '</code></pre>'
                } catch (__) {}
            }

            return '<pre class="hljs"><code>' + md.utils.escapeHtml(str) + '</code></pre>'
        }
    })

    const formatMessage = (content) => {
        if (!content) return ''
        return md.render(content)
    }

    const formatCode = (code, language = '') => {
        if (language && hljs.getLanguage(language)) {
            try {
                return hljs.highlight(code, { language }).value
            } catch (__) {}
        }
        return code
    }

    return {
        formatMessage,
        formatCode,
    }
}

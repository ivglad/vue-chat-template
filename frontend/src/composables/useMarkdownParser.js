/**
 * Композабл для парсинга базового Markdown без дополнительных зависимостей
 * Поддерживает: переносы строк, жирный текст, курсив, код, ссылки
 */

/**
 * Типы токенов для рендера
 */
export const TOKEN_TYPES = {
  TEXT: 'text',
  BOLD: 'bold',
  ITALIC: 'italic',
  CODE: 'code',
  LINK: 'link',
  LINE_BREAK: 'line_break',
  PARAGRAPH: 'paragraph',
}

/**
 * Парсит Markdown текст в массив токенов
 * @param {string} text - исходный markdown текст
 * @returns {Array} массив токенов для рендера
 */
export function parseMarkdown(text) {
  if (!text) return []

  const tokens = []

  // Разбиваем на параграфы (двойные переносы строк)
  const paragraphs = text.split(/\n\s*\n/).filter((p) => p.trim())

  paragraphs.forEach((paragraph, paragraphIndex) => {
    if (paragraphIndex > 0) {
      tokens.push({ type: TOKEN_TYPES.PARAGRAPH, content: '' })
    }

    // Разбиваем параграф на строки
    const lines = paragraph.split('\n')

    lines.forEach((line, lineIndex) => {
      if (lineIndex > 0) {
        tokens.push({ type: TOKEN_TYPES.LINE_BREAK, content: '' })
      }

      const lineTokens = parseInlineMarkdown(line.trim())
      tokens.push(...lineTokens)
    })
  })

  return tokens
}

/**
 * Парсит inline markdown элементы в строке
 * @param {string} line - строка для парсинга
 * @returns {Array} массив токенов
 */
function parseInlineMarkdown(line) {
  if (!line) return []

  const tokens = []
  let currentPos = 0

  // Regex паттерны для разных markdown элементов
  const patterns = [
    // Жирный текст **text**
    { regex: /\*\*(.*?)\*\*/g, type: TOKEN_TYPES.BOLD },
    // Курсив *text*
    { regex: /\*(.*?)\*/g, type: TOKEN_TYPES.ITALIC },
    // Код `text`
    { regex: /`(.*?)`/g, type: TOKEN_TYPES.CODE },
    // Ссылки [text](url)
    { regex: /\[([^\]]+)\]\(([^)]+)\)/g, type: TOKEN_TYPES.LINK },
  ]

  // Находим все совпадения для всех паттернов
  const matches = []
  patterns.forEach((pattern) => {
    let match
    while ((match = pattern.regex.exec(line)) !== null) {
      matches.push({
        type: pattern.type,
        start: match.index,
        end: match.index + match[0].length,
        content: match[1],
        url: match[2], // для ссылок
        fullMatch: match[0],
      })
    }
  })

  // Сортируем совпадения по позиции
  matches.sort((a, b) => a.start - b.start)

  // Удаляем пересекающиеся совпадения (оставляем первые)
  const validMatches = []
  let lastEnd = 0
  matches.forEach((match) => {
    if (match.start >= lastEnd) {
      validMatches.push(match)
      lastEnd = match.end
    }
  })

  // Строим токены
  validMatches.forEach((match) => {
    // Добавляем обычный текст до совпадения
    if (match.start > currentPos) {
      const textContent = line.substring(currentPos, match.start)
      if (textContent) {
        tokens.push({ type: TOKEN_TYPES.TEXT, content: textContent })
      }
    }

    // Добавляем markdown токен
    tokens.push({
      type: match.type,
      content: match.content,
      url: match.url, // для ссылок
    })

    currentPos = match.end
  })

  // Добавляем оставшийся текст
  if (currentPos < line.length) {
    const textContent = line.substring(currentPos)
    if (textContent) {
      tokens.push({ type: TOKEN_TYPES.TEXT, content: textContent })
    }
  }

  // Если не было совпадений, добавляем всю строку как текст
  if (tokens.length === 0 && line) {
    tokens.push({ type: TOKEN_TYPES.TEXT, content: line })
  }

  return tokens
}

/**
 * Композабл для работы с Markdown
 * @returns {Object} объект с функциями парсинга и рендера
 */
export function useMarkdownParser() {
  /**
   * Парсит markdown текст
   * @param {string} text - исходный текст
   * @returns {Array} массив токенов
   */
  const parse = (text) => parseMarkdown(text)

  /**
   * Получает CSS классы для токена
   * @param {Object} token - токен для рендера
   * @returns {string} строка CSS классов
   */
  const getTokenClasses = (token) => {
    const baseClasses = 'inline'

    switch (token.type) {
      case TOKEN_TYPES.BOLD:
        return `${baseClasses} font-bold`
      case TOKEN_TYPES.ITALIC:
        return `${baseClasses} italic`
      case TOKEN_TYPES.CODE:
        return `${baseClasses} bg-gray-100 px-1 py-0.5 rounded text-xs font-mono`
      case TOKEN_TYPES.LINK:
        return `${baseClasses} text-blue-600 hover:text-blue-800 underline cursor-pointer`
      default:
        return baseClasses
    }
  }

  /**
   * Проверяет, нужно ли рендерить токен как блочный элемент
   * @param {Object} token - токен
   * @returns {boolean}
   */
  const isBlockToken = (token) => {
    return (
      token.type === TOKEN_TYPES.LINE_BREAK ||
      token.type === TOKEN_TYPES.PARAGRAPH
    )
  }

  /**
   * Конвертирует токены для совместимости с анимацией текста
   * @param {Array} tokens - массив токенов
   * @returns {Array} массив слов для анимации
   */
  const tokensToWords = (tokens) => {
    const words = []

    tokens.forEach((token) => {
      if (token.type === TOKEN_TYPES.TEXT) {
        // Разбиваем текст на слова
        const tokenWords = token.content
          .split(/\s+/)
          .filter((word) => word.trim())
        tokenWords.forEach((word) => {
          words.push({ text: word, token, isWord: true })
        })
      } else {
        // Включаем все токены, включая блочные
        words.push({ 
          text: token.content || '', 
          token, 
          isWord: false,
          isBlock: isBlockToken(token)
        })
      }
    })

    return words
  }

  /**
   * Конвертирует токены в HTML строку
   * @param {Array} tokens - массив токенов
   * @returns {string} HTML строка
   */
  const tokensToHtml = (tokens) => {
    let html = ''
    let inParagraph = false

    tokens.forEach((token) => {
      switch (token.type) {
        case TOKEN_TYPES.TEXT:
          html += escapeHtml(token.content)
          break
        case TOKEN_TYPES.BOLD:
          html += `<strong>${escapeHtml(token.content)}</strong>`
          break
        case TOKEN_TYPES.ITALIC:
          html += `<em>${escapeHtml(token.content)}</em>`
          break
        case TOKEN_TYPES.CODE:
          html += `<code>${escapeHtml(token.content)}</code>`
          break
        case TOKEN_TYPES.LINK:
          html += `<a href="${escapeHtml(token.url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(token.content)}</a>`
          break
        case TOKEN_TYPES.LINE_BREAK:
          html += '<br>'
          break
        case TOKEN_TYPES.PARAGRAPH:
          if (inParagraph) {
            html += '</p>'
          }
          html += '<p>'
          inParagraph = true
          break
      }
    })

    if (inParagraph) {
      html += '</p>'
    }

    return html
  }

  /**
   * Экранирует HTML символы
   * @param {string} text - текст для экранирования
   * @returns {string} экранированный текст
   */
  const escapeHtml = (text) => {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  }

  /**
   * Парсит markdown и возвращает HTML
   * @param {string} text - исходный markdown текст
   * @returns {string} HTML строка
   */
  const parseMarkdownToHtml = (text) => {
    const tokens = parseMarkdown(text)
    return tokensToHtml(tokens)
  }

  return {
    parse,
    parseMarkdown: parseMarkdownToHtml,
    getTokenClasses,
    isBlockToken,
    tokensToWords,
    tokensToHtml,
    TOKEN_TYPES,
  }
}

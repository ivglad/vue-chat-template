// Сокращение имени до инициалов
export const getInitialsHelper = (fullName, order = [1, 2]) => {
  if (typeof fullName !== 'string') {
    return ''
  }
  if (typeof order !== 'object') {
    order = [1, 2]
  }
  const names = fullName.trim().split(' ')
  return (
    order
      .slice(0, 2)
      .map((i) => names[i - 1]?.[0].toUpperCase())
      .filter(Boolean)
      .join('') || fullName
  )
}

// Форматирование размера файла с учетом конфигурации PrimeVue
export const formatSizeHelper = (
  bytes,
  sizes = ['Б', 'Кб', 'Мб', 'Гб', 'Тб', 'Пб', 'Эб', 'Зб', 'Йб'],
) => {
  const k = 1024
  const dm = 3
  if (bytes === 0) {
    return `0 ${sizes[0]}`
  }
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  const formattedSize = parseFloat((bytes / Math.pow(k, i)).toFixed(dm))
  return `${formattedSize} ${sizes[i]}`
}

import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  apiLoginUser,
  apiLogoutUser,
  apiGetChatHistory,
  apiSendChatMessage,
  apiClearChatHistory,
  apiGetDocuments,
} from './api'

/**
 * Mutation hook for logging in a user.
 *
 * @example
 * const { mutate: loginUserMutation } = useLoginUser()
 * loginUserMutation({ email: 'user@example.com', password: 'password' })
 *
 * @param {Object} data - Login data.
 * @param {string} data.email - User email.
 * @param {string} data.password - User password.
 * @returns {Promise} Promise of the mutation result.
 */
export const useLoginUser = () => {
  return useMutation({
    mutationFn: (data) => {
      return apiLoginUser(data)
    },
  })
}

/**
 * Mutation hook for logging out a user.
 *
 * @example
 * const { mutate: logoutUserMutation } = useLogoutUser()
 * logoutUserMutation()
 *
 * @returns {Promise} Promise of the mutation result.
 */
export const useLogoutUser = () => {
  return useMutation({
    mutationFn: () => {
      return apiLogoutUser()
    },
  })
}

/**
 * Query hook for getting chat history.
 *
 * @example
 * const { data: chatHistory, isLoading } = useChatHistory({ offset: 0, limit: 10 })
 *
 * @param {Object} params - Query parameters.
 * @param {number} params.offset - Offset for pagination (default: 0).
 * @param {number} params.limit - Limit for pagination (default: 10).
 * @returns {Object} Query result with chat history.
 */
export const useChatHistory = (params = {}) => {
  return useQuery({
    queryKey: ['chatHistory', params],
    queryFn: () => apiGetChatHistory(params),
    select: (response) => response.data?.data || {},
  })
}

/**
 * Mutation hook for sending a chat message.
 *
 * @example
 * const { mutate: sendMessage } = useSendChatMessage()
 * sendMessage({ message: 'Привет!', document_ids: [1, 2] })
 *
 * @param {Object} data - Message data.
 * @param {string} data.message - Message text.
 * @param {number[]} data.document_ids - Optional array of document IDs.
 * @returns {Promise} Promise of the mutation result.
 */
export const useSendChatMessage = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (data) => {
      return apiSendChatMessage(data)
    },
    onSuccess: () => {
      // Обновляем историю чата после отправки сообщения
      queryClient.invalidateQueries({ queryKey: ['chatHistory'] })
    },
  })
}

/**
 * Mutation hook for clearing chat history.
 *
 * @example
 * const { mutate: clearHistory } = useClearChatHistory()
 * clearHistory()
 *
 * @returns {Promise} Promise of the mutation result.
 */
export const useClearChatHistory = () => {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => {
      return apiClearChatHistory()
    },
    onSuccess: () => {
      // Обновляем историю чата после очистки
      queryClient.invalidateQueries({ queryKey: ['chatHistory'] })
    },
  })
}

/**
 * Query hook for getting user documents.
 *
 * @example
 * const { data: documents, isLoading } = useDocuments({ per_page: 50 })
 *
 * @param {Object} params - Query parameters.
 * @param {number} params.per_page - Number of documents per page (default: 15).
 * @param {number} params.page - Page number (default: 1).
 * @returns {Object} Query result with documents.
 */
export const useDocuments = (params = {}) => {
  return useQuery({
    queryKey: ['documents', params],
    queryFn: () => apiGetDocuments(params),
    select: (response) => response.data?.data || {},
  })
}

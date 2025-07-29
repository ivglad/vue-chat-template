<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group Документы
 * 
 * API для работы с документами пользователя - получение списка и детальной информации
 */
class DocumentController extends Controller
{
    /**
     * Получить список документов пользователя
     * 
     * Возвращает список документов, доступных пользователю (собственные и по ролям) с пагинацией
     * 
     * @authenticated
     * @queryParam per_page integer Количество документов на страницу (по умолчанию 15, максимум 100) Example: 15
     * @queryParam page integer Номер страницы (по умолчанию 1) Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "documents": [
     *       {
     *         "id": 1,
     *         "title": "Документ 1",
     *         "file_type": "pdf",
     *         "embeddings_generated": true,
     *         "created_at": "2023-12-01T08:00:00.000000Z",
     *         "updated_at": "2023-12-01T08:30:00.000000Z",
     *         "owner": {
     *           "id": 1,
     *           "name": "John Doe"
     *         },
     *         "roles": [
     *           {
     *             "id": 1,
     *             "name": "admin"
     *           }
     *         ]
     *       }
     *     ],
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 15,
     *       "total": 25,
     *       "last_page": 2,
     *       "has_more_pages": true
     *     }
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ]);

        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        try {
            // Получаем документы пользователя и документы доступные по ролям
            $documents = Document::where('user_id', $user->id)
                ->orWhereHas('roles', function ($query) use ($user) {
                    $query->whereIn('roles.id', $user->roles->pluck('id'));
                })
                ->with(['user:id,name', 'roles:id,name'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Форматируем документы для вывода
            $formattedDocuments = $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'file_type' => $document->file_type,
                    'embeddings_generated' => $document->embeddings_generated,
                    'created_at' => $document->created_at->toISOString(),
                    'updated_at' => $document->updated_at->toISOString(),
                    'owner' => [
                        'id' => $document->user->id,
                        'name' => $document->user->name,
                    ],
                    'roles' => $document->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'documents' => $formattedDocuments,
                    'pagination' => [
                        'current_page' => $documents->currentPage(),
                        'per_page' => $documents->perPage(),
                        'total' => $documents->total(),
                        'last_page' => $documents->lastPage(),
                        'has_more_pages' => $documents->hasMorePages(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('API Documents index error', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при получении документов',
            ], 500);
        }
    }

    /**
     * Получить конкретный документ
     * 
     * Возвращает детальную информацию о конкретном документе, если у пользователя есть к нему доступ
     * 
     * @authenticated
     * @urlParam id integer required ID документа Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "document": {
     *       "id": 1,
     *       "title": "Документ 1",
     *       "google_docs_url": "https://docs.google.com/document/d/...",
     *       "content": "Содержимое документа...",
     *       "file_path": "/path/to/file.pdf",
     *       "file_type": "pdf",
     *       "embeddings_generated": true,
     *       "created_at": "2023-12-01T08:00:00.000000Z",
     *       "updated_at": "2023-12-01T08:30:00.000000Z",
     *       "owner": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "roles": [
     *         {
     *           "id": 1,
     *           "name": "admin"
     *         }
     *       ],
     *       "embeddings_count": 15
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Документ не найден или нет доступа"
     * }
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            // Получаем документ с проверкой доступа
            $document = Document::where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('roles', function ($roleQuery) use ($user) {
                            $roleQuery->whereIn('roles.id', $user->roles->pluck('id'));
                        });
                })
                ->with(['user:id,name', 'roles:id,name', 'embeddings'])
                ->find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Документ не найден или нет доступа',
                ], 404);
            }

            // Форматируем документ для вывода
            $formattedDocument = [
                'id' => $document->id,
                'title' => $document->title,
                'google_docs_url' => $document->google_docs_url,
                'content' => $document->content,
                'file_path' => $document->file_path,
                'file_type' => $document->file_type,
                'embeddings_generated' => $document->embeddings_generated,
                'created_at' => $document->created_at->toISOString(),
                'updated_at' => $document->updated_at->toISOString(),
                'owner' => [
                    'id' => $document->user->id,
                    'name' => $document->user->name,
                ],
                'roles' => $document->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
                'embeddings_count' => $document->embeddings->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'document' => $formattedDocument,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('API Documents show error', ['error' => $e->getMessage(), 'user_id' => $user->id, 'document_id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при получении документа',
            ], 500);
        }
    }
} 
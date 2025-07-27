<script setup>
const toast = useToast()
const onUploadMessage = () => {
  toast.add({
    severity: 'success',
    summary: 'Успешно',
    detail: 'Файл загружен',
    life: 3000,
  })
}
const onErrorUploadMessage = (e) => {
  upsertUploadErrorFiles(e.files)
  toast.add({
    severity: 'error',
    summary: 'Ошибка',
    detail: 'Не удалось загрузить файл',
    life: 3000,
  })
}

const uploadErrorFiles = ref([])
const isFileUploadingError = (file) => {
  return uploadErrorFiles.value.find(
    (uploadFile) => uploadFile.objectURL === file.objectURL,
  )
}
const upsertUploadErrorFiles = (files) => {
  uploadErrorFiles.value.push(...files)
}

const onRemoveFile = (removeFileCallback, index) => {
  removeFileCallback(index)
}

// Определение вариантов и состояний для загрузки файлов
const fileUploadStates = ['Default']
const fileUploadVariants = ['Default']
</script>

<template>
  <LayoutUiTemplate
    title="File upload"
    :states="fileUploadStates"
    :variants="fileUploadVariants">
    <!-- Default -->
    <template #default-default>
      <FileUpload
        name="requestParameter[]"
        url="/api/upload"
        pt:root:class="app-fileupload"
        pt:header:class="app-fileupload-header"
        pt:content:class="app-fileupload-content"
        pt:empty:class="app-fileupload-empty"
        invalidFileSizeMessage="{0}:Размер файла не должен превышать {1}"
        invalidFileLimitMessage="Количество загружаемых файлов не должно превышать {0}"
        invalidFileTypeMessage="{0}:Неправильный тип файла"
        :fileLimit="10"
        accept="image/*"
        :maxFileSize="1000000"
        multiple
        :showCancelButton="false"
        :showUploadButton="false"
        @upload="onUploadMessage($event)"
        @error="onErrorUploadMessage($event)">
        <template #empty>
          <span>Перетащите файлы сюда или выберите вручную</span>
        </template>
        <template #header="{ chooseCallback, uploadCallback, files }">
          <Button label="Прикрепить" @click="chooseCallback">
            <template #icon>
              <i-custom-plus class="app-button-icon-right" />
            </template>
          </Button>
        </template>
        <template
          #content="{
            files,
            uploadedFiles,
            removeUploadedFileCallback,
            removeFileCallback,
            messages,
          }">
          <Message
            v-for="message of messages"
            :key="message"
            size="small"
            severity="error"
            closable>
            {{ message }}
          </Message>
          <div v-if="files?.length" class="app-fileupload-files">
            <div
              class="app-fileupload-file"
              v-for="(file, index) of files"
              :key="file.name + file.type + file.size">
              <div class="app-fileupload-file-info">
                <span
                  class="app-fileupload-file-name"
                  v-tooltip.top="{
                    value: file.name,
                    showDelay: 500,
                  }">
                  {{ file.name }}
                </span>
                <Message severity="secondary" variant="simple" size="small">
                  {{ formatSizeHelper(file.size) }}
                </Message>
              </div>
              <Button
                variant="text"
                severity="secondary"
                rounded
                @click="onRemoveFile(removeFileCallback, index)">
                <template #icon>
                  <i-custom-close />
                </template>
              </Button>
            </div>
          </div>
          <div v-if="uploadedFiles?.length">
            <div
              class="app-fileupload-file"
              v-for="(file, index) of uploadedFiles"
              :key="file.name + file.type + file.size">
              <div class="app-fileupload-file-info">
                <span
                  class="app-fileupload-file-name"
                  v-tooltip.top="{
                    value: file.name,
                    showDelay: 500,
                  }">
                  {{ file.name }}
                </span>
                <Message severity="secondary" variant="simple" size="small">
                  {{ formatSizeHelper(file.size) }}
                </Message>
              </div>

              <Badge
                v-if="isFileUploadingError(file)"
                value="Ошибка"
                severity="danger"
                size="small" />

              <Button
                variant="text"
                severity="secondary"
                rounded
                @click="
                  removeUploadedFileCallback(file, removeFileCallback, index)
                ">
                <template #icon>
                  <i-custom-close />
                </template>
              </Button>
            </div>
          </div>
        </template>
      </FileUpload>
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>

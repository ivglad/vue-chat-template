# Docwise Bot - Project Overview

## Purpose
Docwise Bot is an intelligent document processing and chat system that enables users to upload documents, process them with AI embeddings, and interact with the content through both web and Telegram interfaces.

## Core Features
- 📚 Document upload and indexing via Filament admin panel
- 🤖 YandexGPT integration for embeddings and AI responses
- 💬 Telegram bot for mobile document queries
- 🌐 Web chat interface with markdown support
- 🎨 Automatic response formatting for different interfaces
- 👥 User management and role-based access control
- 🔍 Semantic document search using vector embeddings

## Tech Stack

### Backend (Laravel)
- **Framework**: Laravel 12+ with PHP 8.2+
- **Database**: PostgreSQL with pgvector extension for vector embeddings
- **Cache**: Redis for session and cache management
- **Admin Panel**: Filament 3.0+ for administrative interface
- **AI Integration**: YandexGPT API for embeddings and chat responses
- **Bot Framework**: Telegram Bot SDK
- **Document Processing**: PDF parsing, Word documents, audio transcription
- **File Management**: Spatie Media Library
- **Permissions**: Spatie Laravel Permission for role-based access

### Frontend (Vue.js)
- **Framework**: Vue.js 3 with Composition API
- **Build Tool**: Vite 7+ for development and building
- **UI Framework**: PrimeVue 4.3+ with custom theming
- **Styling**: TailwindCSS 4+ with SCSS preprocessing
- **State Management**: Pinia for application state
- **HTTP Client**: Axios with TanStack Vue Query for API calls
- **Animations**: Motion-v for smooth transitions
- **Auto Imports**: Unplugin Auto Import for Vue, PrimeVue, and utilities

## Architecture
- **Fullstack**: Separate backend (Laravel API) and frontend (Vue SPA)
- **Containerized**: Docker with docker-compose for deployment
- **Vector Search**: pgvector for semantic document search
- **Queue System**: Laravel queues for background processing
- **Multi-channel**: Web interface + Telegram bot
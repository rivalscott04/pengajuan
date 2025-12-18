@props(['documents', 'requirements'])

@php
    $canVerify = auth()->user()?->hasRole('operator_kanwil');

    $documentMeta = $documents->map(function ($doc) use ($requirements) {
        $label = $requirements[$doc->document_type]['label'] ?? $doc->document_type;
        $isPdf = str_ends_with(strtolower($doc->path), '.pdf');

        return [
            'id' => $doc->id,
            'label' => $label,
            'is_pdf' => $isPdf,
            'is_verified' => (bool) ($doc->is_verified ?? false),
            'download_url' => route('submission.document.download', ['document' => $doc->id]),
            'toggle_url' => route('submission.document.toggleVerification', ['document' => $doc->id]),
        ];
    })->values();
@endphp

<div x-data="pdfViewer(@js($documentMeta), @js($canVerify))" class="space-y-4">
    {{-- Document Tabs --}}
    <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
        @foreach($documents as $index => $doc)
            <button 
                type="button"
                @click="selectDocument({{ $index }})"
                :class="activeTab === {{ $index }} ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            >
                <span class="flex items-center gap-2">
                    <span>{{ $requirements[$doc->document_type]['label'] ?? $doc->document_type }}</span>
                    <span 
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold"
                        :class="documents[{{ $index }}]?.is_verified 
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' 
                            : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'"
                    >
                        <span 
                            class="w-1.5 h-1.5 rounded-full mr-1.5"
                            :class="documents[{{ $index }}]?.is_verified ? 'bg-green-500' : 'bg-yellow-500'"
                        ></span>
                        <span x-text="documents[{{ $index }}]?.is_verified ? 'Terverifikasi' : 'Belum diverifikasi'"></span>
                    </span>
                </span>
            </button>
        @endforeach
    </div>

    {{-- PDF Viewer Container --}}
    <div class="relative bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden" style="height: 70vh;">
        {{-- Loading Indicator --}}
        <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 z-10">
            <div class="flex flex-col items-center gap-3">
                <svg class="animate-spin h-10 w-10 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">Memuat dokumen...</span>
            </div>
        </div>

        {{-- PDF Canvas Container --}}
        <div x-show="isPdf && !loading" class="h-full flex flex-col">
            {{-- PDF Controls --}}
            <div class="flex items-center justify-between px-4 py-2 bg-gray-200 dark:bg-gray-700">
                <div class="flex items-center gap-2">
                    <button type="button" @click="prevPage()" :disabled="currentPage <= 1" class="p-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        Halaman <span x-text="currentPage"></span> dari <span x-text="totalPages"></span>
                    </span>
                    <button type="button" @click="nextPage()" :disabled="currentPage >= totalPages" class="p-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
                <div class="flex items-center gap-4">
                    <div x-show="documents.length > 0 && canVerify" class="flex items-center gap-2">
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                            :class="documents[activeTab]?.is_verified 
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' 
                                : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'"
                        >
                            <span 
                                class="w-2 h-2 rounded-full mr-1.5"
                                :class="documents[activeTab]?.is_verified ? 'bg-green-500' : 'bg-yellow-500'"
                            ></span>
                            <span x-text="documents[activeTab]?.is_verified ? 'Terverifikasi' : 'Belum diverifikasi'"></span>
                        </span>
                        <button
                            type="button"
                            @click="toggleVerification()"
                            class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <span x-text="documents[activeTab]?.is_verified ? 'Batalkan Verifikasi' : 'Tandai Terverifikasi'"></span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                    <button type="button" @click="zoomOut()" class="p-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path></svg>
                    </button>
                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="Math.round(scale * 100) + '%'"></span>
                    <button type="button" @click="zoomIn()" class="p-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path></svg>
                    </button>
                    <a :href="currentUrl" target="_blank" class="p-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600" title="Buka di tab baru">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                    <a :href="currentUrl" download class="p-2 rounded hover:bg-gray-300 dark:hover:bg-gray-600" title="Download">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </a>
                </div>
            </div>
            {{-- PDF Canvas --}}
            <div class="flex-1 overflow-auto flex justify-center p-4">
                <canvas x-ref="pdfCanvas" class="shadow-lg"></canvas>
            </div>
        </div>

        {{-- Non-PDF File Download --}}
        <div x-show="!isPdf && !loading" class="h-full flex items-center justify-center">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <p class="text-gray-600 dark:text-gray-400 mb-4">File ini bukan PDF</p>
                <a :href="currentUrl" download class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download File
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    // Set worker source
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    
    function pdfViewer(initialDocuments = [], canVerify = false) {
        return {
            documents: initialDocuments,
            canVerify: canVerify,
            activeTab: 0,
            loading: true,
            isPdf: true,
            currentUrl: '',
            pdfDoc: null,
            currentPage: 1,
            totalPages: 0,
            scale: 1.0,
            
            init() {
                // Load first document on init
                if (this.documents.length > 0) {
                    this.selectDocument(0);
                }
            },

            async selectDocument(index) {
                if (!this.documents[index]) return;

                this.activeTab = index;
                const doc = this.documents[index];
                await this.loadPdf(doc.download_url, doc.is_pdf);
            },

            async toggleVerification() {
                if (!this.canVerify || !this.documents.length) return;

                const doc = this.documents[this.activeTab];
                if (!doc || !doc.toggle_url) return;

                try {
                    const token = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content');

                    const response = await fetch(doc.toggle_url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token ?? '',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Gagal menyimpan status verifikasi dokumen.');
                    }

                    const data = await response.json();
                    doc.is_verified = !!data.is_verified;
                } catch (error) {
                    console.error(error);
                    alert('Gagal mengubah status verifikasi dokumen.');
                }
            },
            
            async loadPdf(url, isPdfFile) {
                this.loading = true;
                this.currentUrl = url;
                this.isPdf = isPdfFile;
                
                if (!isPdfFile) {
                    this.loading = false;
                    return;
                }
                
                try {
                    this.pdfDoc = await pdfjsLib.getDocument(url).promise;
                    this.totalPages = this.pdfDoc.numPages;
                    this.currentPage = 1;
                    this.scale = 1.0;
                    await this.renderPage();
                } catch (error) {
                    console.error('Error loading PDF:', error);
                    this.isPdf = false;
                }
                
                this.loading = false;
            },
            
            async renderPage() {
                if (!this.pdfDoc) return;
                
                const page = await this.pdfDoc.getPage(this.currentPage);
                const canvas = this.$refs.pdfCanvas;
                const ctx = canvas.getContext('2d');
                
                const viewport = page.getViewport({ scale: this.scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                await page.render({
                    canvasContext: ctx,
                    viewport: viewport
                }).promise;
            },
            
            async prevPage() {
                if (this.currentPage <= 1) return;
                this.currentPage--;
                await this.renderPage();
            },
            
            async nextPage() {
                if (this.currentPage >= this.totalPages) return;
                this.currentPage++;
                await this.renderPage();
            },
            
            async zoomIn() {
                this.scale = Math.min(this.scale + 0.25, 3.0);
                await this.renderPage();
            },
            
            async zoomOut() {
                this.scale = Math.max(this.scale - 0.25, 0.5);
                await this.renderPage();
            }
        };
    }
</script>



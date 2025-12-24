<template>
    <button
        @click="handleDownload"
        :class="buttonClasses"
        :disabled="loading"
        :title="title"
    >
        <i :class="iconClass" class="mr-2 text-2xl"></i>
        {{ buttonTextComputed }}  <!-- Cambiar buttonText por buttonTextComputed -->
    </button>
</template>

<script>
export default {
    props: {
        url: {
            type: String,
            required: true
        },
        buttonText: {
            type: String,
            default: ''
        },
        loadingText: {
            type: String,
            default: 'Descargando...'
        },
        variant: {
            type: String,
            default: 'primary'
        },
        size: {
            type: String,
            default: 'md'
        },
        forcedFilename: {
            type: String,
            default: null
        },
        iconReport: {
            type: String,
            default: 'fas fa-download'
        },
    },
    data() {
        return {
            loading: false,
            downloadedFilename: null
        }
    },
    computed: {
        title() {
            if (this.downloadedFilename) {
                return `Descargar: ${this.downloadedFilename}`;
            }
            return `Descargar ${this.buttonText}`;
        },
        iconClass() {
            return this.loading ? 'fas fa-spinner fa-spin' : `${this.iconReport}`;
        },
        buttonTextComputed() {
            return this.loading ? this.loadingText : this.buttonText;
        },
        buttonClasses() {
            const base = 'inline-flex items-center rounded-md font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2';

            const sizes = {
                sm: 'px-3 py-1 text-sm',
                md: 'px-4 py-2 text-base',
                lg: 'px-6 py-3 text-lg'
            };

            const variants = {
                primary: this.loading
                    ? 'bg-gray-400 text-gray-700 cursor-not-allowed'
                    : 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500',
                secondary: this.loading
                    ? 'bg-gray-400 text-gray-700 cursor-not-allowed'
                    : 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
                outline: this.loading
                    ? 'border border-rose-600 text-rose-600 hover:bg-rose-50 focus:ring-rose-500 cursor-not-allowed'
                    : 'border border-gray-300 text-gray-400 hover:bg-gray-700 focus:ring-gray-500'
            };

            return `${base} ${sizes[this.size]} ${variants[this.variant]}`;
        }
    },
    methods: {
        async handleDownload() {
            if (this.loading) return;

            this.loading = true;

            try {
                const response = await fetch(this.url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) throw new Error('Error en la descarga');

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;

                let filename = this.forcedFilename;

                if (!filename) {
                    const contentDisposition = response.headers.get('content-disposition');
                    if (contentDisposition) {
                        const match = contentDisposition.match(/filename="([^"]*)"/);
                        if (match && match[1]) {
                            filename = match[1];
                        } else {
                            const match2 = contentDisposition.match(/filename=([^;]*)/);
                            if (match2 && match2[1]) {
                                filename = match2[1].trim();
                            }
                        }
                    }
                }

                if (!filename) {
                    const now = new Date();
                    const dateStr = now.toISOString().slice(0, 19).replace(/[-:]/g, '').replace('T', '_');
                    filename = `data_user_${dateStr}.csv`;
                }

                // Eliminar el guión bajo final si existe
                if (filename.endsWith('_')) {
                    filename = filename.slice(0, -1);
                }

                if (!filename.toLowerCase().endsWith('.csv')) {
                    filename += '.csv';
                }

                this.downloadedFilename = filename;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);

                this.$emit('downloaded', filename);

            } catch (error) {
                console.error('Error al descargar:', error);
                this.$emit('error', error);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

export default {
    props: {
        balance: String,
        name: String
    },
    data() {
        return {
            showModal: false,
            modalType: '',
            form: {
                amount: '',
                recipient: ''
            },
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
            errorMessage: '',
            successMessage: '',
            localBalance: this.balance,
            isLoading: false,
            successTimeout: null,
            transactions: [],
            isReverting: {}
        }
    },
    computed: {
        formattedBalance() {
            return (parseFloat(this.localBalance) / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    },

    async created() {
        await this.fetchTransactions();
    },
    methods: {
        openModal(type) {
            this.modalType = type;
            this.showModal = true;
            this.form.amount = '';
            this.form.recipient = '';
        },
        closeModal() {
            this.showModal = false;
            this.modalType = '';
        },
        closeSuccessModal() {
            if (this.successTimeout) {
                clearTimeout(this.successTimeout);
                this.successTimeout = null;
            }
            this.successMessage = '';
        },
        async submitForm() {
            this.isLoading = true;
            try {
                let response;
                if (this.modalType === 'deposit') {
                    response = await axios.post('/wallet/deposit', {
                        amount: parseFloat(this.form.amount)
                    });
                    this.successMessage = 'Depósito feito com sucesso!';
                } else {
                    response = await axios.post('/wallet/transfer', {
                        amount: this.form.amount,
                        recipient: this.form.recipient
                    });
                    this.successMessage = 'Transferência feita com sucesso!';
                }
                this.localBalance = (response.data * 100).toString();
                this.closeModal();
                this.successTimeout = setTimeout(() => {
                    this.successMessage = '';
                    this.successTimeout = null;
                }, 2000);
                await this.fetchTransactions();
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Ocorreu um erro ao processar a operação.';
            } finally {
                this.isLoading = false;
            }
        },
        async fetchTransactions() {
            try {
                const response = await axios.get('/wallet/history');
                this.transactions = response.data;
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erro ao carregar o histórico de transações.';
            }
        },
        async revertTransaction(transactionId) {
            try {
                const response = await axios.post('/wallet/revert', {
                    transaction_id: transactionId
                });
                this.localBalance = response.data.toString();
                await this.fetchTransactions();
                this.successMessage = 'Transação revertida com sucesso!';
                this.successTimeout = setTimeout(() => {
                    this.successMessage = '';
                    this.successTimeout = null;
                }, 2000);
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erro ao reverter a transação.';
            }
        },

        formatAmount(amount) {
            return (parseFloat(amount)).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDate(date) {
            return new Date(date).toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        async logout() {
            try {
                await axios.post('/logout', {}, {
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken
                    }
                });
                window.location.href = '/login';
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'Erro ao fazer logout. Tente novamente.';
            }
        }
    }
} 
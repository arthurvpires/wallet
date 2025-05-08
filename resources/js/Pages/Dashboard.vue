<template>
    <nav class="bg-blue-900 fixed w-full top-0 z-10">
        <div class="flex justify-end p-4">
            <form @submit.prevent="logout" method="POST">
                <input type="hidden" name="_token" :value="csrfToken" />
                <button type="submit" class="text-white font-semibold hover:text-blue-200 bg-transparent border-none">
                    Sair
                </button>
            </form>
        </div>
    </nav>

    <div class="bg-gray-100 min-h-screen p-4 pt-20 w-full flex flex-col items-center overflow-auto">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg mb-6">
            <h1 class="text-2xl font-semibold text-gray-800 mb-4">
                {{ `Olá, ${name}!` }}
            </h1>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-center">
                <p class="text-md text-gray-600">Saldo Atual</p>
                <p class="text-3xl font-bold text-blue-900 mt-1">R$ {{ formattedBalance }}</p>
            </div>
            <div class="flex justify-center gap-4">
                <button @click="openModal('deposit')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                    Depositar
                </button>
                <button @click="openModal('transfer')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                    Transferir
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-6xl">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Histórico de Transações</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-2 px-4 text-left text-gray-600 font-semibold">Tipo</th>
                            <th class="py-2 px-4 text-left text-gray-600 font-semibold">Valor</th>
                            <th class="py-2 px-4 text-left text-gray-600 font-semibold">Destinatário</th>
                            <th class="py-2 px-4 text-left text-gray-600 font-semibold">Data</th>
                            <th class="py-2 px-4 text-left text-gray-600 font-semibold">Transação foi revertida?</th>
                            <th class="py-2 px-4 text-left text-gray-600 font-semibold">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="transaction in transactions" :key="transaction.id" class="border-t">
                            <td class="py-2 px-4 capitalize">{{
                                transaction.type === 'deposit' ? 'Depósito' :
                                    transaction.type === 'received_transfer' ? 'Transferência recebida' :
                                        'Transferência'
                            }}</td>
                            <td class="py-2 px-4">R$ {{ formatAmount(transaction.amount) }}</td>
                            <td class="py-2 px-4">{{ transaction.recipient_email || '-' }}</td>
                            <td class="py-2 px-4">{{ formatDate(transaction.created_at) }}</td>
                            <td class="py-2 px-4">
                                <span v-if="transaction.was_reverted" class="text-green-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mx-auto" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span v-else class="text-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mx-auto" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </td>
                            <td class="py-2 px-4">
                                <button  @click="revertTransaction(transaction.id)"
                                    :disabled="transaction.status === 'reverted' || isReverting[transaction.id]"
                                    class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-1 px-3 rounded-lg text-sm"
                                    :class="{ 'opacity-50 cursor-not-allowed': transaction.status === 'reverted' || isReverting[transaction.id] }">
                                    Reverter
                                    <span v-if="isReverting[transaction.id]">...</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!transactions.length" class="text-center">
                            <td colspan="5" class="py-4 text-gray-600">Nenhuma transação encontrada.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                {{ modalType === 'deposit' ? 'Depositar' : 'Transferir' }}
            </h2>
            <form @submit.prevent="submitForm">
                <div class="mb-4">
                    <label for="amount" class="block text-gray-600 text-sm mb-1">Valor</label>
                    <input v-model="form.amount" type="number" id="amount" step="0.01" min="0" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="R$ 0,00" />
                </div>
                <div v-if="modalType === 'transfer'" class="mb-4">
                    <label for="recipient" class="block text-gray-600 text-sm mb-1">Destinatário</label>
                    <input v-model="form.recipient" type="email" id="recipient"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="email@exemplo.com" />
                </div>
                <div class="flex justify-end gap-4">
                    <button type="button" @click="closeModal"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="isLoading"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg">
                        {{ modalType === 'deposit' ? 'Depositar' : 'Transferir' }}
                        <span v-if="isLoading">...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-sm text-center shadow-lg">
            <h2 class="text-xl font-bold text-red-600 mb-4">Erro</h2>
            <p class="text-gray-700 mb-6">{{ errorMessage }}</p>
            <button @click="errorMessage = ''"
                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg">
                Fechar
            </button>
        </div>
    </div>

    <div v-if="successMessage" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-sm text-center shadow-lg">
            <h2 class="text-xl font-bold text-green-600 mb-4">Sucesso</h2>
            <p class="text-gray-700 mb-4">{{ successMessage }}</p>
            <div class="flex justify-center mb-4">
                <svg class="circular-progress" width="32" height="32" viewBox="0 0 32 32">
                    <circle class="progress-background" cx="16" cy="16" r="14" fill="none" stroke="#e5e7eb"
                        stroke-width="4" />
                    <circle class="progress-foreground" cx="16" cy="16" r="14" fill="none" stroke="#16a34a"
                        stroke-width="4" stroke-dasharray="87.964" stroke-dashoffset="87.964" />
                </svg>
            </div>
            <button @click="closeSuccessModal"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg">
                Fechar
            </button>
        </div>
    </div>
</template>

<script>
import DashboardScript from './dashboard.js';
import '../../css/pages/dashboard.css';

export default DashboardScript;
</script>

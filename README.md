# Wallet Challenge

Este é um projeto de carteira digital desenvolvido com Laravel e Vue.js, utilizando Inertia.js para uma experiência de usuário moderna e responsiva.

## 🚀 Tecnologias Utilizadas

- PHP 8.3
- Laravel 12
- Vue.js
- Inertia.js
- MySQL 8.0
- Docker
- Nginx
- Tailwind CSS

## 📋 Pré-requisitos

- Docker e Docker Compose
- Git
- Node.js (para desenvolvimento local)

## 🔧 Instalação

1. Clone o repositório:
```bash
git clone https://github.com/arthurvpires/wallet.git
cd wallet
```

2. Configure o ambiente:
```bash
cp .env.example .env
```

3. Inicie os containers Docker:
```bash
docker-compose up -d --build
```

4. Instale as dependências do PHP:
```bash
docker exec -it wallet-challange composer install
```

5. Instale as dependências do Node.js:
```bash
npm install
```

6. Gere a chave da aplicação:
```bash
docker exec -it wallet-challange php artisan key:generate
```

7. Execute as migrações do banco de dados:
```bash
docker exec -it wallet-challange php artisan migrate
```

8. Compile os assets:
```bash
npm run build
```
## 🌐 Configuração do Hosts

### Windows
1. Abra o Bloco de Notas como administrador
2. Abra o arquivo: `C:\Windows\System32\drivers\etc\hosts`
3. Adicione a seguinte linha:
```
127.0.0.1 wallet-challange.local
```

### Linux/Mac
1. Abra o terminal
2. Execute o comando:
```bash
sudo nano /etc/hosts
```
3. Adicione a seguinte linha:
```
127.0.0.1 wallet-challange.local
```
## 🏃‍♂️ Executando o Projeto

O projeto estará disponível em:
- wallet-challange.local

Para desenvolvimento local, você pode usar:
```bash
npm run dev
```

## 🧪 Testes

Para executar os testes criados:
```bash
docker exec -it wallet-challange php artisan test --filter WalletControllerTest 
docker exec -it wallet-challange php artisan test --filter WalletServiceTest 
```

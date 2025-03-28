# TAVERNA DA IMPRESSÃO E-commerce

Sistema de e-commerce especializado em materiais impressos para RPG, incluindo fichas, mapas, livros e materiais personalizados para jogadores.

## Estrutura do Projeto

```
/taverna-impressao/
├── public/              # Arquivos públicos
│   ├── index.php        # Ponto de entrada
│   ├── assets/          # Recursos estáticos
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   └── uploads/         # Arquivos enviados pelos usuários
├── app/                 # Código da aplicação
│   ├── controllers/     # Controladores
│   ├── models/          # Modelos de dados
│   ├── views/           # Templates visuais
│   ├── helpers/         # Funções auxiliares
│   └── config/          # Configurações
└── database/            # Migrações e seeds
```

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)

## Instalação

1. Clone o repositório
2. Importe o esquema do banco de dados em `database/schema.sql`
3. Configure o arquivo `app/config/config.php` com suas credenciais
4. Configure o servidor web para apontar para a pasta `public/`

## Desenvolvimento

O projeto segue a arquitetura MVC:

- **Models**: Responsáveis pela interação com o banco de dados
- **Views**: Templates de exibição
- **Controllers**: Gerenciam a lógica de negócios

## Funcionalidades

- Catálogo de produtos
- Sistema de usuários e autenticação
- Carrinho de compras
- Sistema de personalização de produtos
- Checkout e pagamentos
- Painel administrativo
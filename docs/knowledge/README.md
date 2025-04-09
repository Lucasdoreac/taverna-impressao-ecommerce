# Base de Conhecimento Técnico da Taverna da Impressão 3D

## Introdução

Este diretório contém a documentação técnica e a base de conhecimento do projeto Taverna da Impressão 3D, organizada por domínios. O objetivo é centralizar o conhecimento técnico da plataforma, facilitando a manutenção, evolução e transferência de conhecimento entre a equipe.

## Estrutura do Diretório

- **arquitetura/** - Documentos relacionados à arquitetura geral do sistema
- **seguranca/** - Protocolos e implementações de segurança
- **performance/** - Otimizações e considerações de performance
- **padroes/** - Padrões de design e codificação adotados
- **integracao/** - Documentação sobre integração entre componentes
- **desenvolvimento/** - Guias de desenvolvimento e convenções

## Documentos Principais

### Arquitetura e Design

- [arquitetura_sistema.md](arquitetura/arquitetura_sistema.md) - Visão geral da arquitetura
- [fluxo_dados.md](arquitetura/fluxo_dados.md) - Diagramas de fluxo de dados
- [modelagem_banco_dados.md](arquitetura/modelagem_banco_dados.md) - Esquema e modelagem do banco de dados

### Segurança

- [guardrails.md](seguranca/guardrails.md) - Guardrails de segurança obrigatórios
- [csrf_protection.md](seguranca/csrf_protection.md) - Implementação da proteção CSRF
- [input_validation.md](seguranca/input_validation.md) - Validação e sanitização de entrada
- [file_security.md](seguranca/file_security.md) - Segurança de uploads e manipulação de arquivos

### Performance

- [caching_strategy.md](performance/caching_strategy.md) - Estratégias de cache na aplicação
- [optimized_queries.md](performance/optimized_queries.md) - Otimização de consultas SQL
- [quotation_system_optimization_technical.md](quotation_system_optimization_technical.md) - Otimizações do Sistema de Cotação Automatizada

### Componentes Especializados

- [authentication_system.md](componentes/authentication_system.md) - Sistema de autenticação
- [print_queue_system.md](componentes/print_queue_system.md) - Sistema de fila de impressão
- [model_validation.md](componentes/model_validation.md) - Sistema de validação de modelos 3D

## Como Contribuir para a Base de Conhecimento

1. **Mantenha a documentação atualizada**: Ao implementar um novo recurso ou modificar um existente, atualize a documentação relevante
2. **Siga o template**: Use o template `_template.md` como base para novos documentos
3. **Referencie código**: Inclua referências ao código-fonte sempre que possível
4. **Use exemplos concretos**: Adicione exemplos de código e casos de uso para ilustrar conceitos
5. **Mantenha a rastreabilidade**: Documente decisões arquiteturais e suas justificativas

## Padrões para Documentação

### Formato

- Use Markdown para todos os documentos
- Inclua um cabeçalho com título, data de última atualização e autores
- Estruture documentos com seções claras usando títulos com níveis adequados (##, ###)
- Use listas, tabelas e blocos de código quando apropriado

### Conteúdo

- Comece com uma visão geral clara e concisa
- Inclua diagramas quando apropriado (usando PlantUML, Mermaid ou imagens)
- Documente as interfaces e contratos entre componentes
- Descreva considerações de segurança e performance para cada componente
- Liste possíveis problemas conhecidos e suas soluções

## Padrões de Segurança Obrigatórios

Todos os documentos técnicos desta base de conhecimento devem aderir aos guardrails de segurança do projeto:

1. Nunca documentar senhas, chaves de API ou segredos
2. Sempre incluir considerações de segurança relevantes
3. Documentar validação de entrada para todos os componentes de interface
4. Destacar a necessidade de proteção CSRF em todas as operações POST
5. Enfatizar o uso de prepared statements para todas as operações de banco de dados
6. Documentar sanitização de saída para prevenção de XSS
7. Incluir verificações de permissão para operações sensíveis
8. Documentar o uso de headers HTTP de segurança

## Documentos de Referência

- [PLANNING.md](../../PLANNING.md) - Planejamento geral do projeto
- [TASK.md](../../TASK.md) - Tarefas em andamento e concluídas
- [docs/continuity/optimization_implementation_status.md](../continuity/optimization_implementation_status.md) - Status das otimizações em implementação

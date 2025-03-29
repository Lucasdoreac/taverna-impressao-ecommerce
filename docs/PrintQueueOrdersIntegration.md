# Documentação da Integração entre Sistema de Pedidos e Fila de Impressão 3D

## Visão Geral

Este documento descreve a integração implementada entre o sistema de pedidos e a fila de impressão 3D para a Taverna da Impressão. Esta integração permite o fluxo completo desde a criação do pedido até a impressão e entrega do produto 3D.

## Arquitetura da Integração

A integração é baseada em um modelo de comunicação bidirecional:

1. **Pedido → Fila**: Quando um pedido é criado, itens que precisam ser impressos em 3D são adicionados à fila de impressão.
2. **Fila → Pedido**: Atualizações na fila de impressão (como conclusão da impressão, falhas, etc.) atualizam automaticamente o status do pedido.

## Componentes Principais

### 1. Modelos

- **OrderModel**: Gerencia os pedidos e seus itens
- **PrintQueueModel**: Gerencia a fila de impressão 3D
- **NotificationModel**: Gerencia notificações enviadas aos clientes e administradores
- **SettingsModel**: Gerencia configurações do sistema

### 2. Controladores

- **OrderController**: Controla o fluxo de pedidos e sua integração com a fila
- **PrintQueueController**: Controla a fila de impressão 3D
- **NotificationController**: Gerencia as notificações

### 3. Visões

- **Admin/pedidos**: Interfaces para gerenciamento de pedidos
- **Admin/print_queue**: Interfaces para gerenciamento da fila de impressão
- **Cliente/track**: Interfaces para o cliente acompanhar a impressão

## Fluxos de Trabalho

### 1. Fluxo Básico Produtos Testados (Pronta Entrega)

```
Cliente faz pedido → Sistema verifica estoque → Produto separado → Pedido enviado
```

### 2. Fluxo Básico Produtos Sob Encomenda

```
Cliente faz pedido → Item adicionado à fila → Impressão agendada → Impressão 
→ Acabamento → Pedido enviado
```

### 3. Fluxo Detalhado de Impressão 3D

1. **Criação do Pedido**:
   - Cliente faz um pedido contendo produtos de impressão 3D
   - Sistema identifica itens que precisam ser impressos

2. **Adição à Fila**:
   - OrderController adiciona itens à fila via PrintQueueModel
   - Uma notificação é enviada ao cliente

3. **Gerenciamento da Fila**:
   - Administrador visualiza a fila em Admin/print_queue
   - Define prioridade e atribui impressoras
   - Cada mudança de status é registrada no histórico

4. **Impressão**:
   - Administrador inicia a impressão
   - Status do item na fila é atualizado para "printing"
   - Cliente recebe notificação de início

5. **Conclusão**:
   - Quando a impressão é concluída, o status é atualizado
   - NotificationModel cria notificação de conclusão
   - OrderModel atualiza status do pedido para "finishing"

6. **Entrega**:
   - Após acabamento, produto é enviado
   - Status do pedido é atualizado para "shipped"
   - Cliente recebe notificação de envio

## Status de Impressão 3D

| Status | Descrição | Efeito no Pedido |
|--------|-----------|------------------|
| pending | Aguardando na fila | Pedido mantém "processing" |
| scheduled | Agendado para impressão | Pedido mantém "processing" |
| printing | Em processo de impressão | Pedido atualizado para "printing" |
| paused | Impressão pausada | Pedido mantém status |
| completed | Impressão finalizada | Pedido atualizado para "finishing" |
| failed | Falha na impressão | Pedido atualizado para "problem" |
| canceled | Impressão cancelada | Pedido pode ser cancelado |

## Integrações de Notificações

O sistema de notificações envia alertas aos clientes nos seguintes eventos:

1. **Adição à fila**: Item adicionado com sucesso
2. **Atribuição de impressora**: Impressora designada para o produto
3. **Início da impressão**: Impressão iniciada com tempo estimado
4. **Conclusão**: Impressão concluída com sucesso
5. **Falha**: Problemas durante a impressão
6. **Acabamento**: Produto em fase final antes do envio

As notificações são visíveis na área do cliente e podem ser configuradas na área administrativa.

## Cache e Otimização de Desempenho

O sistema implementa várias otimizações:

1. **Cache em memória**: Consultas frequentes são armazenadas em cache
2. **Consultas otimizadas**: JOINs desnecessários são evitados em consultas frequentes
3. **Processamento em lote**: Atualizações em massa são feitas em uma única consulta
4. **Limpeza automática**: Notificações antigas são removidas periodicamente

## Interface Administrativa

### Dashboard da Fila

O dashboard fornece:

- Totais de itens por status
- Impressoras disponíveis e em uso
- Tempo estimado para conclusão da fila
- Próximos itens a serem impressos
- Histórico recente de impressões

### Relatórios de Produção

Relatórios detalhados incluem:

- Estatísticas de uso de impressoras
- Consumo de filamento por tipo e cor
- Tempos médios de impressão
- Taxa de sucesso/falha por impressora

## Fluxo de Dados Entre Sistemas

```
+----------------+           +---------------+           +----------------+
|                |           |               |           |                |
|  ORDER SYSTEM  |<--------->| PRINT QUEUE   |<--------->| NOTIFICATION   |
|                |           |               |           |                |
+----------------+           +---------------+           +----------------+
      ^                             ^                           ^
      |                             |                           |
      v                             v                           v
+----------------+           +---------------+           +----------------+
|                |           |               |           |                |
|  CUSTOMER UI   |<--------->| ADMIN UI      |<--------->| SETTINGS       |
|                |           |               |           |                |
+----------------+           +---------------+           +----------------+
```

## Extensões Futuras

1. **API para Impressoras**: Integração direta com software de controle de impressoras
2. **Monitoramento em Tempo Real**: Câmeras e sensores para acompanhamento da impressão
3. **Estimativas de Custo Automatizadas**: Cálculo preciso de tempo e material
4. **Pré-Verificação de Modelos**: Validação automática de arquivos 3D
5. **Notificações por Email/SMS**: Além das notificações no sistema

## Solução de Problemas Comuns

### "Status do pedido e da fila não sincronizados"

Verificar:
- Execute `OrderController::syncOrderWithQueue($orderId)`
- Verifique as entradas em `print_queue_history` para o item específico

### "Notificações não estão sendo enviadas"

Verificar:
- Configurações em `settings` para "notifications_*"
- Logs de erro relacionados à `NotificationModel`
- Permissões do usuário para receber notificações

## Referências

- `app/models/PrintQueueModel.php`: Gerenciamento da fila de impressão
- `app/models/NotificationModel.php`: Sistema de notificações
- `app/controllers/OrderController.php`: Controlador de pedidos
- `app/controllers/PrintQueueController.php`: Controlador da fila de impressão
- `app/controllers/NotificationController.php`: Controlador de notificações

---

*Documentação v1.0 - Taverna da Impressão 3D - Data: 29/03/2025*

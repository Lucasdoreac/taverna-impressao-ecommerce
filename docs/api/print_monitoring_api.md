# API de Monitoramento de Impressões 3D - Documentação

## Visão Geral

Esta API permite a integração de impressoras 3D e sistemas externos com o sistema de monitoramento em tempo real da Taverna da Impressão. 

Com esta API, você pode:
- Iniciar uma nova impressão
- Atualizar o status e progresso de uma impressão em andamento
- Enviar métricas detalhadas sobre a impressão (temperatura, camadas, etc.)
- Consultar detalhes sobre impressões ativas

## Autenticação

Todas as solicitações à API requerem uma chave de API (`api_key`) e um identificador de impressora (`printer_id`). Estas credenciais devem ser obtidas junto à administração da Taverna da Impressão.

## Endpoints

### 1. Iniciar uma Nova Impressão

Inicia o monitoramento de uma nova impressão.

**URL**: `/api/status/start`  
**Método**: `POST`  
**Formato**: `application/json`

**Parâmetros**:
```json
{
  "api_key": "sua_chave_api",
  "printer_id": "PRINTER001",
  "print_queue_id": 123
}
```

**Resposta de Sucesso**:
```json
{
  "success": true,
  "message": "Status de impressão criado com sucesso",
  "print_status_id": 456
}
```

### 2. Atualizar Status de Impressão

Atualiza o status, progresso, ou adiciona uma mensagem a uma impressão em andamento.

**URL**: `/api/status/update`  
**Método**: `POST`  
**Formato**: `application/json`

**Parâmetros**:
```json
{
  "api_key": "sua_chave_api",
  "printer_id": "PRINTER001",
  "print_status_id": 456,
  "status": "printing",
  "progress": 25.5,
  "message": "Camada 50 de 200 concluída",
  "metrics": {
    "hotend_temp": 210.5,
    "bed_temp": 60.2,
    "speed_percentage": 100,
    "fan_speed_percentage": 80,
    "layer_height": 0.2,
    "current_layer": 50,
    "total_layers": 200,
    "filament_used_mm": 1250.5,
    "print_time_remaining_seconds": 3600
  }
}
```

**Observações**:
- Todos os campos exceto `api_key`, `printer_id` e `print_status_id` são opcionais
- Pelo menos um dos campos opcionais deve ser fornecido
- O campo `status` deve ser um dos seguintes valores: `pending`, `preparing`, `printing`, `paused`, `completed`, `failed`, `canceled`

**Resposta de Sucesso**:
```json
{
  "success": true,
  "message": "Status atualizado com sucesso",
  "current_status": {
    "id": 456,
    "status": "printing",
    "progress_percentage": 25.5,
    /* ... outros detalhes do status ... */
  }
}
```

### 3. Consultar Status de Impressão

Obtém detalhes sobre um status de impressão específico.

**URL**: `/api/status/{id}`  
**Método**: `GET`

**Parâmetros de URL**:
- `id`: ID do status de impressão

**Parâmetros de Query (opcionais)**:
- `api_key`: Sua chave de API
- `printer_id`: ID da sua impressora

**Observações**:
- Se `api_key` e `printer_id` forem fornecidos e válidos, retorna detalhes completos
- Caso contrário, retorna apenas informações básicas

**Resposta de Sucesso**:
```json
{
  "id": 456,
  "status": "printing",
  "progress_percentage": 25.5,
  "started_at": "2025-03-31 09:45:00",
  "estimated_completion": "2025-03-31 12:45:00",
  /* ... outros detalhes se autenticado ... */
}
```

### 4. Listar Impressões Ativas para uma Impressora

Lista todas as impressões ativas atualmente atribuídas a uma impressora específica.

**URL**: `/api/status/printer/{printer_id}`  
**Método**: `GET`

**Parâmetros de URL**:
- `printer_id`: ID da impressora

**Parâmetros de Query**:
- `api_key`: Sua chave de API (obrigatório)

**Resposta de Sucesso**:
```json
{
  "printer_id": "PRINTER001",
  "active_jobs_count": 2,
  "jobs": [
    {
      "print_status_id": 456,
      "status": "printing",
      "formatted_status": "Imprimindo",
      "progress_percentage": 25.5,
      "product_id": 789,
      "product_name": "Miniatura de Dragão",
      "model_file": "dragon.stl",
      "order_id": 123,
      "started_at": "2025-03-31 09:45:00",
      "last_updated": "2025-03-31 10:15:00"
    },
    /* ... outros trabalhos ... */
  ]
}
```

## Códigos de Status

A API utiliza os seguintes códigos de status HTTP:

- `200`: Sucesso
- `400`: Requisição inválida (parâmetros ausentes ou inválidos)
- `401`: Autenticação inválida
- `404`: Recurso não encontrado
- `409`: Conflito (por exemplo, quando já existe um status para o item da fila)
- `500`: Erro no servidor

## Exemplos de Uso

### Exemplo 1: Iniciar uma Impressão

```bash
curl -X POST https://tavernadaimpressao.com.br/api/status/start \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "sua_chave_api",
    "printer_id": "PRINTER001",
    "print_queue_id": 123
  }'
```

### Exemplo 2: Atualizar Progresso

```bash
curl -X POST https://tavernadaimpressao.com.br/api/status/update \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "sua_chave_api",
    "printer_id": "PRINTER001",
    "print_status_id": 456,
    "progress": 50.2,
    "metrics": {
      "current_layer": 100,
      "total_layers": 200,
      "hotend_temp": 210.5,
      "bed_temp": 60.2
    }
  }'
```

### Exemplo 3: Marcar como Concluído

```bash
curl -X POST https://tavernadaimpressao.com.br/api/status/update \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "sua_chave_api",
    "printer_id": "PRINTER001",
    "print_status_id": 456,
    "status": "completed",
    "progress": 100,
    "message": "Impressão concluída com sucesso!"
  }'
```

## Implementação em Clientes de Impressora

### OctoPrint

Para integrar com OctoPrint, você pode usar o plugin "OctoPrint-TavernaMonitor", disponível em nosso repositório GitHub. Alternativamente, você pode configurar o plugin "OctoPrint-MQTT" para enviar atualizações para nossa API através de um script intermediário.

### Outras Impressoras

Para outras impressoras ou sistemas de controle, você pode implementar chamadas periódicas à nossa API durante o processo de impressão. Sugerimos intervalos de 15-30 segundos para atualizações de progresso e camadas, e intervalos maiores (1-2 minutos) para atualizações de métricas completas.

## Considerações de Segurança

- Armazene sua `api_key` de forma segura
- Use HTTPS para todas as solicitações à API
- Não compartilhe sua `api_key` em repositórios públicos ou scripts expostos
- Considere implementar um limite de taxa para evitar sobrecarga da API

## Suporte e Feedback

Em caso de dúvidas ou problemas com a API, entre em contato com nossa equipe técnica pelo e-mail api-support@tavernadaimpressao.com.br ou abra uma issue em nosso repositório GitHub.

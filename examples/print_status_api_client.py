#!/usr/bin/env python3
"""
Cliente de Exemplo para a API de Monitoramento de Impressões 3D da Taverna da Impressão

Este script demonstra como integrar uma impressora 3D ou sistema externo com
a API de monitoramento de impressões da Taverna da Impressão.

Uso:
    python print_status_api_client.py start <print_queue_id>
    python print_status_api_client.py update <print_status_id> <progress> [status]
    python print_status_api_client.py complete <print_status_id>
    python print_status_api_client.py get <print_status_id>
    python print_status_api_client.py list

Requisitos:
    pip install requests

Autor: Equipe Taverna da Impressão
Data: 31/03/2025
"""

import sys
import time
import json
import random
import argparse
import requests
from datetime import datetime, timedelta

# Configurações
API_BASE_URL = "https://tavernadaimpressao.com.br/api"
API_KEY = "your_api_key_here"  # Substitua pela sua chave de API
PRINTER_ID = "PRINTER001"       # Substitua pelo ID da sua impressora

# Headers padrão para requisições
DEFAULT_HEADERS = {
    "Content-Type": "application/json",
    "Accept": "application/json"
}

def api_request(endpoint, method="GET", data=None, params=None):
    """Faz uma requisição à API"""
    url = f"{API_BASE_URL}/{endpoint}"
    
    try:
        if method == "GET":
            response = requests.get(url, headers=DEFAULT_HEADERS, params=params)
        elif method == "POST":
            response = requests.post(url, headers=DEFAULT_HEADERS, json=data)
        else:
            print(f"Método HTTP não suportado: {method}")
            return None
            
        # Verificar se a requisição foi bem-sucedida
        response.raise_for_status()
        
        # Retornar dados da resposta
        return response.json()
    
    except requests.exceptions.RequestException as e:
        print(f"Erro na requisição: {e}")
        if hasattr(e, 'response') and e.response is not None:
            try:
                error_data = e.response.json()
                print(f"Erro retornado pela API: {error_data.get('error', 'Desconhecido')}")
            except:
                print(f"Código de status HTTP: {e.response.status_code}")
        return None

def start_print(print_queue_id):
    """Inicia uma nova impressão"""
    data = {
        "api_key": API_KEY,
        "printer_id": PRINTER_ID,
        "print_queue_id": print_queue_id
    }
    
    print(f"Iniciando nova impressão para o item da fila #{print_queue_id}...")
    result = api_request("status/start", method="POST", data=data)
    
    if result and result.get("success"):
        print(f"Impressão iniciada com sucesso! ID do status: {result.get('print_status_id')}")
        return result.get('print_status_id')
    else:
        print("Falha ao iniciar impressão.")
        return None

def update_print(print_status_id, progress, status=None):
    """Atualiza o status e progresso de uma impressão"""
    # Preparar dados básicos
    data = {
        "api_key": API_KEY,
        "printer_id": PRINTER_ID,
        "print_status_id": print_status_id,
        "progress": progress
    }
    
    # Adicionar status se fornecido
    if status:
        data["status"] = status
    
    # Gerar métricas simuladas para demonstração
    metrics = generate_sample_metrics(progress)
    if metrics:
        data["metrics"] = metrics
    
    print(f"Atualizando impressão #{print_status_id} - Progresso: {progress}%...")
    result = api_request("status/update", method="POST", data=data)
    
    if result and result.get("success"):
        print(f"Impressão atualizada com sucesso!")
        current_status = result.get('current_status', {})
        print(f"Status atual: {current_status.get('status')} - Progresso: {current_status.get('progress_percentage')}%")
        return True
    else:
        print("Falha ao atualizar impressão.")
        return False

def complete_print(print_status_id):
    """Marca uma impressão como concluída"""
    data = {
        "api_key": API_KEY,
        "printer_id": PRINTER_ID,
        "print_status_id": print_status_id,
        "status": "completed",
        "progress": 100,
        "message": "Impressão concluída com sucesso!"
    }
    
    print(f"Marcando impressão #{print_status_id} como concluída...")
    result = api_request("status/update", method="POST", data=data)
    
    if result and result.get("success"):
        print("Impressão marcada como concluída com sucesso!")
        return True
    else:
        print("Falha ao marcar impressão como concluída.")
        return False

def get_print_status(print_status_id):
    """Obtém detalhes de um status de impressão"""
    params = {
        "api_key": API_KEY,
        "printer_id": PRINTER_ID
    }
    
    print(f"Obtendo detalhes da impressão #{print_status_id}...")
    result = api_request(f"status/{print_status_id}", params=params)
    
    if result:
        print("Dados da impressão:")
        print(json.dumps(result, indent=2))
        return result
    else:
        print("Falha ao obter detalhes da impressão.")
        return None

def list_printer_jobs():
    """Lista todos os trabalhos ativos da impressora"""
    params = {
        "api_key": API_KEY
    }
    
    print(f"Listando trabalhos ativos para a impressora {PRINTER_ID}...")
    result = api_request(f"status/printer/{PRINTER_ID}", params=params)
    
    if result:
        jobs_count = result.get('active_jobs_count', 0)
        print(f"Total de trabalhos ativos: {jobs_count}")
        
        if jobs_count > 0:
            print("\nTrabalhos ativos:")
            for job in result.get('jobs', []):
                print(f"ID: {job.get('print_status_id')} - Produto: {job.get('product_name')} - Progresso: {job.get('progress_percentage')}% - Status: {job.get('formatted_status')}")
        return result
    else:
        print("Falha ao listar trabalhos da impressora.")
        return None

def simulate_full_print(print_queue_id, duration_minutes=30, update_interval_seconds=10):
    """Simula uma impressão completa, do início ao fim"""
    print(f"Iniciando simulação de impressão para o item da fila #{print_queue_id}")
    print(f"Duração estimada: {duration_minutes} minutos")
    print(f"Intervalo de atualização: {update_interval_seconds} segundos")
    
    # Iniciar a impressão
    print_status_id = start_print(print_queue_id)
    if not print_status_id:
        return False
    
    # Calcular número total de atualizações
    total_updates = (duration_minutes * 60) // update_interval_seconds
    progress_increment = 100 / total_updates
    
    # Simular progresso
    current_progress = 0
    for i in range(total_updates):
        # Esperar o intervalo
        time.sleep(update_interval_seconds)
        
        # Calcular novo progresso
        current_progress += progress_increment
        current_progress = min(99.9, current_progress)  # Limitar a 99.9%
        
        # Escolher um status apropriado
        status = None
        if i == 0:
            status = "printing"  # Primeira atualização, iniciar impressão
        elif random.random() < 0.05:  # 5% de chance de pausar/retomar
            if random.random() < 0.5:
                status = "paused"
                print("Simulando pausa temporária...")
            else:
                status = "printing"
                print("Retomando impressão...")
        
        # Enviar atualização
        success = update_print(print_status_id, current_progress, status)
        if not success:
            print("Simulação interrompida devido a um erro.")
            return False
    
    # Marcar como concluída
    return complete_print(print_status_id)

def generate_sample_metrics(progress):
    """Gera métricas simuladas para uma impressão"""
    # Calcular valores de métricas baseados no progresso
    current_layer = int(progress * 2)  # Exemplo: 200 camadas no total
    total_layers = 200
    
    # Métricas de temperatura (variação aleatória para simular condições reais)
    hotend_temp = 210 + random.uniform(-1.5, 1.5)
    bed_temp = 60 + random.uniform(-0.8, 0.8)
    
    # Calcular tempo restante estimado (diminui à medida que o progresso aumenta)
    remaining_minutes = (100 - progress) * 0.6  # Exemplo: 60 minutos total
    remaining_seconds = int(remaining_minutes * 60)
    
    return {
        "hotend_temp": round(hotend_temp, 1),
        "bed_temp": round(bed_temp, 1),
        "speed_percentage": random.randint(95, 105),
        "fan_speed_percentage": random.randint(80, 100),
        "layer_height": 0.2,
        "current_layer": current_layer,
        "total_layers": total_layers,
        "filament_used_mm": round(progress * 100, 1),  # Exemplo: 10000mm total
        "print_time_remaining_seconds": remaining_seconds
    }

def main():
    parser = argparse.ArgumentParser(description="Cliente para API de Monitoramento de Impressões 3D")
    subparsers = parser.add_subparsers(dest="command", help="Comando a executar")
    
    # Comando: start
    start_parser = subparsers.add_parser("start", help="Iniciar uma nova impressão")
    start_parser.add_argument("print_queue_id", type=int, help="ID do item na fila de impressão")
    
    # Comando: update
    update_parser = subparsers.add_parser("update", help="Atualizar o status de uma impressão")
    update_parser.add_argument("print_status_id", type=int, help="ID do status de impressão")
    update_parser.add_argument("progress", type=float, help="Progresso atual (0-100)")
    update_parser.add_argument("status", nargs="?", help="Status (opcional: printing, paused, etc.)")
    
    # Comando: complete
    complete_parser = subparsers.add_parser("complete", help="Marcar uma impressão como concluída")
    complete_parser.add_argument("print_status_id", type=int, help="ID do status de impressão")
    
    # Comando: get
    get_parser = subparsers.add_parser("get", help="Obter detalhes de uma impressão")
    get_parser.add_argument("print_status_id", type=int, help="ID do status de impressão")
    
    # Comando: list
    list_parser = subparsers.add_parser("list", help="Listar trabalhos ativos da impressora")
    
    # Comando: simulate
    simulate_parser = subparsers.add_parser("simulate", help="Simular uma impressão completa")
    simulate_parser.add_argument("print_queue_id", type=int, help="ID do item na fila de impressão")
    simulate_parser.add_argument("--duration", type=int, default=30, help="Duração em minutos (padrão: 30)")
    simulate_parser.add_argument("--interval", type=int, default=10, help="Intervalo de atualização em segundos (padrão: 10)")
    
    args = parser.parse_args()
    
    if args.command == "start":
        start_print(args.print_queue_id)
    elif args.command == "update":
        update_print(args.print_status_id, args.progress, args.status)
    elif args.command == "complete":
        complete_print(args.print_status_id)
    elif args.command == "get":
        get_print_status(args.print_status_id)
    elif args.command == "list":
        list_printer_jobs()
    elif args.command == "simulate":
        simulate_full_print(args.print_queue_id, args.duration, args.interval)
    else:
        parser.print_help()

if __name__ == "__main__":
    main()

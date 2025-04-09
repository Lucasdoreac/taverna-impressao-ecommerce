/*
 * Teste de carga usando k6 (https://k6.io/)
 * Para executar: k6 run k6-load-test.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Métricas personalizadas
const errorRate = new Rate('errors');

// Configuração do teste
export const options = {
  stages: [
    { duration: '1m', target: 50 },  // Ramp-up para 50 usuários
    { duration: '3m', target: 50 },  // Manter 50 usuários por 3 minutos
    { duration: '1m', target: 100 }, // Ramp-up para 100 usuários
    { duration: '5m', target: 100 }, // Manter 100 usuários por 5 minutos
    { duration: '1m', target: 0 },   // Ramp-down para 0 usuários
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% das requisições devem ser concluídas em menos de 500ms
    errors: ['rate<0.1'],             // Taxa de erro deve ser menor que 10%
  },
};

// Função para obter token CSRF (simulada)
function getCsrfToken() {
  const res = http.get('https://taverna-impressao-ecommerce.local/get-csrf-token');
  if (res.status !== 200) {
    console.error('Falha ao obter token CSRF');
    return null;
  }
  
  try {
    const data = JSON.parse(res.body);
    return data.csrf_token;
  } catch (e) {
    console.error('Erro ao processar resposta de token CSRF');
    return null;
  }
}

// Lista de tokens de processo para teste
// Em um caso real, esses tokens seriam gerados previamente
const processTokens = [
  'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
  'q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2',
  'g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8',
  'w9x0y1z2a3b4c5d6e7f8g9h0i1j2k3l4',
  'm5n6o7p8q9r0s1t2u3v4w5x6y7z8a9b0'
];

// Cenário principal do teste
export default function() {
  // Obter um token CSRF uma vez por sessão de usuário
  const csrfToken = getCsrfToken();
  if (!csrfToken) {
    errorRate.add(1);
    return;
  }
  
  // Selecionar aleatoriamente um token de processo
  const processToken = processTokens[Math.floor(Math.random() * processTokens.length)];
  
  // Testar API de verificação de status
  const payload = {
    process_token: processToken,
    csrf_token: csrfToken
  };
  
  const params = {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-CSRF-Token': csrfToken
    },
  };
  
  const res = http.post(
    'https://taverna-impressao-ecommerce.local/api/status-check', 
    payload, 
    params
  );
  
  // Verificar resposta
  const success = check(res, {
    'status is 200': (r) => r.status === 200,
    'response has data': (r) => {
      try {
        const data = JSON.parse(r.body);
        return data.success === true && data.data !== undefined;
      } catch (e) {
        return false;
      }
    },
  });
  
  if (!success) {
    errorRate.add(1);
    console.log(`Falha na requisição: ${res.status}, ${res.body}`);
  }
  
  // Pausa entre requisições para simular comportamento real do usuário
  sleep(Math.random() * 3 + 1); // 1-4 segundos
}

// Função para limpar após o teste
export function teardown() {
  console.log('Teste de carga concluído');
}

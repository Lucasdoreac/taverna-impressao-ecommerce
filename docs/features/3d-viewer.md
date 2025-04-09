# Sistema de Visualização 3D - Documentação Técnica

## Visão Geral

O Sistema de Visualização 3D permite aos usuários visualizar modelos 3D (STL, OBJ, 3MF) diretamente no navegador web. Este sistema foi implementado para aumentar a usabilidade da plataforma, permitindo que os usuários visualizem seus modelos sem a necessidade de softwares especializados instalados localmente.

## Arquitetura e Componentes

O sistema de visualização 3D é composto pelos seguintes componentes:

### 1. Viewer3DController

Controlador responsável por gerenciar a visualização dos modelos 3D. Principais responsabilidades:

- Renderizar a interface de visualização 3D
- Fornecer acesso seguro aos arquivos de modelo 3D
- Gerenciar permissões de visualização e download
- Aplicar headers HTTP seguros ao servir os arquivos

### 2. View do Visualizador 3D

Interface de usuário do sistema de visualização 3D, com recursos como:

- Controles de rotação, zoom e pan
- Opções de visualização (wireframe, grid, auto-rotação)
- Controles de iluminação e cor de fundo
- Exibição de metadados técnicos do modelo

### 3. Script JavaScript (model-viewer.js)

Script client-side que utiliza Three.js para renderizar os modelos 3D no navegador. Funcionalidades:

- Carregamento de diferentes formatos de arquivos 3D (STL, OBJ, 3MF)
- Controles interativos de câmera e visualização
- Manipulação do modelo (rotação, zoom, pan)
- Renderização com iluminação avançada e materiais
- Interface responsiva que se adapta a diferentes dispositivos

### 4. Integração com o Sistema de Modelos

O sistema de visualização 3D está integrado ao sistema existente de gestão de modelos, permitindo:

- Visualização direta a partir da listagem de modelos
- Acesso a partir da página de detalhes do modelo
- Validação de permissões baseada no status do modelo (apenas modelos aprovados)

## Diagrama de Fluxo

```
┌───────────────┐     Acessa     ┌───────────────┐
│               │───────────────>│               │
│    Usuário    │                │   Listagem/   │
│               │<───────────────│  Detalhes do  │
└───────────────┘     Exibe      │    Modelo     │
        │                        └───────────────┘
        │ Clica em "Visualizar 3D"
        ▼
┌───────────────┐     Renderiza  ┌───────────────┐
│               │───────────────>│               │
│ Viewer3DView  │                │ View com UI do│
│               │<───────────────│  Visualizador │
└───────────────┘     Exibe      └───────────────┘
        │                               │
        │ Carrega script                │ Interação
        ▼                               ▼
┌───────────────┐     Solicita   ┌───────────────┐
│               │───────────────>│               │
│ model-viewer.js│               │ Three.js +    │
│               │<───────────────│  WebGL        │
└───────────────┘    Renderiza   └───────────────┘
        │                               │
        │ Solicita arquivo              │ Exibe modelo
        ▼                               ▼
┌───────────────┐     Verifica   ┌───────────────┐
│               │───────────────>│               │
│   getModel    │                │  Modelo 3D    │
│               │<───────────────│  Renderizado  │
└───────────────┘     Serve      └───────────────┘
```

## Medidas de Segurança

O sistema implementa diversas medidas de segurança:

### 1. Validação de Entrada e Autorização

- Validação robusta de parâmetros de entrada (ID, token)
- Verificação de autenticação do usuário
- Validação do status do modelo (apenas modelos aprovados)
- Verificação de propriedade para operações sensíveis

### 2. Proteção CSRF

- Tokens CSRF utilizados em todas as operações que modificam estado
- Validação rigorosa de tokens usando `CsrfProtection::validateToken()`
- Tokens únicos por sessão e com tempo de expiração

### 3. Headers HTTP Seguros

- Content-Type apropriado para cada tipo de modelo 3D
- Headers de segurança via `SecurityManager::setSecureHeaders()`
- Cache-Control adequado para otimizar a entrega de conteúdo

### 4. Proteção de Arquivos

- Prevenção de path traversal na obtenção de arquivos
- Acesso controlado via tokens com expiração
- Verificação explícita de existência e tipo de arquivo

## Uso da Biblioteca Three.js

O sistema utiliza a biblioteca Three.js para a renderização 3D no navegador. Os principais componentes utilizados são:

- **Three.js Core**: Renderização WebGL básica
- **OrbitControls**: Controles de câmera interativos
- **Loaders**: STLLoader, OBJLoader, 3MFLoader para diferentes formatos

A implementação segue as melhores práticas para otimização de performance:

- Carregamento assíncrono de modelos
- Configuração apropriada de luzes e materiais
- Redimensionamento e centralização automática de modelos
- Técnicas de otimização de renderização

## Fluxo de Dados e Carregamento de Modelos

1. O usuário acessa a página do visualizador 3D
2. O controller renderiza a view com as configurações iniciais
3. O script JavaScript (model-viewer.js) é carregado
4. O script solicita o arquivo do modelo via URL segura
5. O controller valida o token e serve o arquivo com headers apropriados
6. O script carrega o modelo usando o loader apropriado
7. O modelo é processado, centralizado e renderizado na tela
8. O usuário interage com o modelo através dos controles da interface

## Limitações Atuais

- Arquivos muito grandes (>50MB) podem causar problemas de performance
- Alguns recursos avançados de OBJ (materiais externos) e 3MF (texturas) não são totalmente suportados
- Compatibilidade limitada com navegadores antigos que não suportam WebGL

## Extensibilidade e Futuras Melhorias

O sistema foi projetado para ser facilmente extensível. Possíveis melhorias futuras:

1. **Suporte a mais formatos**: GLTF, FBX, DAE (Collada)
2. **Carregamento progressivo**: Para modelos maiores
3. **Anotações e medições**: Adicionar capacidade de medir distâncias e adicionar anotações
4. **Exportação em diferentes formatos**: Conversão entre formatos no navegador
5. **Visualização em AR/VR**: Integração com tecnologias de Realidade Aumentada e Virtual

## Dependências

- Three.js r128 (CDN)
- Componentes adicionais do Three.js: OrbitControls, STLLoader, OBJLoader, 3MFLoader

## Desempenho e Otimização

O sistema inclui várias otimizações para garantir bom desempenho:

- Detecção automática do tamanho do modelo para ajuste da câmera
- Aplicação de escala automática para normalização
- Otimização de luzes e sombras
- Controle responsivo do tamanho do canvas
- Processamento assíncrono para evitar bloqueio da interface

## Considerações de Segurança Adicionais

- **Sandbox de Execução**: O JavaScript do visualizador é executado em um escopo isolado
- **Validação de Conteúdo**: Verificação de integridade do modelo antes da renderização
- **Proteção Contra Overflow**: Limites nos controles de câmera para evitar problemas

## Exemplo de Uso no Código

Para adicionar um link para o visualizador 3D em outras partes do sistema:

```php
<?php if ($model['status'] === 'approved'): ?>
    <a href="<?= BASE_URL ?>viewer3d/view/<?= $model['id'] ?>" class="btn btn-primary">
        <i class="fas fa-cube"></i> Visualizar em 3D
    </a>
<?php endif; ?>
```

## Código Exemplo para Carregar um Modelo Programaticamente

```javascript
// Inicializar o visualizador com configurações personalizadas
const customConfig = {
    modelUrl: '/viewer3d/get-model/123/TOKEN',
    backgroundColor: '#2c3e50',
    gridEnabled: true,
    autoRotate: true,
    defaultCameraPosition: [0, 0, 10],
    wireframeEnabled: false
};

const viewer = new ModelViewer('container-id', customConfig);
viewer.load();
```

## Testes de Segurança Realizados

1. **Validação de Entrada**: Testados diferentes valores de ID e token para garantir rejeição adequada
2. **Verificação de Permissões**: Confirmado que apenas usuários autorizados podem acessar os modelos
3. **Headers de Segurança**: Verificada a presença e configuração correta dos headers HTTP
4. **Proteção de Arquivos**: Tentativas de path traversal foram detectadas e bloqueadas
5. **Cross-Site Request Forgery**: Validação de tokens CSRF para prevenir ataques

## Logs e Monitoramento

O sistema registra informações importantes para monitoramento e auditoria:

- Acessos a modelos (quem visualizou, quando)
- Downloads de modelos (por usuário, data/hora)
- Erros de carregamento e renderização
- Tentativas de acesso não autorizado

Estes logs são importantes para identificação de problemas e melhorias futuras.

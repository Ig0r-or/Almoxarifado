<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'auth_functions.php';
startSecureSession();

require 'db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT u.*, c.nome as cargo_nome 
                      FROM usuarios u
                      JOIN cargos c ON u.cargo_id = c.id
                      WHERE u.id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>almoxarifado</title>
</head>
<body>
    <header class="header">
        <div class="header-icon">
            <a>
            <img src="db7b8b7d-e7c2-4dd6-a55d-76ed85275d8c.png" alt="Ícone"/>
            </a>
        </div>
        <div class="header-title">Olá, <?= htmlspecialchars($usuario['nome']) ?> (<?= htmlspecialchars($usuario['cargo_nome']) ?>)</div>
        <div class="button-container">
            <button class="page-button" id="btnImprimir"></button>
            <button class="theme-button" onclick="toggleDarkMode()"></button>
            <a href="logout.php" class="logout-button"> <i class="fas fa-sign-out-alt"></i>Sair</a>
        </div>
    </header>
<!----------------------------------------------------------------------------------------------------------------------------------------------->
    <form class="tabela-container">
    <table id="tabelaItens" border="1">
        <thead>
            <tr>
              <th>ID</th>
              <th>Itens</th>
              <th>Quantidade</th>
              <th>Janeiro</th>
              <th>Fevereiro</th>
              <th>Março</th>
              <th>Abril</th>
              <th>Maio</th>
              <th>Junho</th>
              <th>Julho</th>
              <th>Agosto</th>
              <th>Setembro</th>
              <th>Outubro</th>
              <th>Novembro</th>
              <th>Dezembro</th>
              <th>Saldo</th>
              <th>v2.4</th>
            </tr>
        </thead>
           <tbody>
           <!-- Itens serão carregados aqui -->
          </tbody>
      </table>
      </form>
        <form id="formAdicionar">
        <button type="submit" class="page-button">Adicionar Item</button>
    </form>
<!----------------------------------------------------------------------------------------------------------------------------------------------->
<!-- Slider de Imagens -->
<div class="slider-container">
    <h2>Info</h2>
    <div class="slider">
    </div>
     <div class="slider-controls">
        <button class="prev-btn"><i class="fas fa-chevron-left"></i></button>
        <button class="add-image-btn" id="openAddImageModal">
            <i class="fas fa-plus"></i>
        </button>
        <button class="next-btn"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="slider-dots"></div>
</div>
<div id="addImageModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Adicionar Nova Imagem</h3>
        <form id="uploadImageForm">
            <div class="form-group">
                <label for="itemSelect">Selecione o Item:</label>
                <select id="itemSelect" required>
                    <option value="">Carregando itens...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="imageUpload">Escolha a imagem:</label>
                <input type="file" id="imageUpload" accept="image/*" required>
            </div>
            <button type="submit" class="submit-btn">Enviar</button>
        </form>
        <div id="uploadStatus"></div>
    </div>
</div>
<!----------------------------------------------------------------------------------------------------------------------------------------------->

    <div class="form-container" id="form-container">
    <h2>Formulário de Retirada</h2>
    <form id="formSolicitacao" method="POST">
    
        <div class="form-group">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" readonly required>
        </div>

        <div class="form-group">
            <label for="data">Data:</label>
            <input type="text" id="data" name="data" readonly>
            <input type="hidden" id="mes" name="mes" value="">
        </div>

        <div class="form-group">
            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label for="item">Item:</label>
            <select id="item" name="item" required>
                <option value="">Carregando itens...</option>
            </select>
        </div>

        <div class="form-group">
            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade" min="1" required>
        </div>

        <button type="submit" class="submit-button" id="formSolicitacao">Enviar</button>
    </form>
</div>
 <div id="entrada-popup"> 
        <h2>Histórico &uarr;</h2> 
        <ul id="historico-entrada"></ul> 
    </div>
<!----------------------------------------------------------------------------------------------------------------------------------------------->
    <script>
     document.getElementById("formAdicionar").addEventListener("submit", function(event) {
             // Evita o recarregamento da página

            let xhr = new XMLHttpRequest();
            xhr.open("POST", "adicionar_item.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function() {
                if (xhr.status === 200) {
                    console.log("Resposta do servidor:", xhr.responseText);
                     loadTableData();
                    alert("Novo item adicionado com sucesso!");
                } else {
                    console.error("Erro na requisição:", xhr.statusText);
                    alert("Erro ao adicionar item.");
                }
            };

            xhr.onerror = function() {
                console.error("Erro de conexão.");
                alert("Erro de conexão com o servidor.");
            };

            // Envia a requisição sem dados, pois queremos apenas adicionar um item vazio
            xhr.send();
        });

        // Função para deletar historico-------------------------------------------------------------------------------------------------

        function deleteHistoricoItem(id) {
            if (confirm("Tem certeza que deseja deletar o histórico?")) {
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "deletar_historico.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        console.log("Resposta do servidor:", xhr.responseText);
                        carregarHistorico(); // Recarrega os dados da tabela após deletar o item
                    } else {
                        console.error("Erro na requisição:", xhr.statusText);
                    }
                };

                xhr.onerror = function() {
                    console.error("Erro de conexão.");
                };

                // Envia o ID do item a ser deletado
                let data = "action=delete&id=" + id;
                xhr.send(data);
            }
        }

        // Carrega os dados da tabela ao carregar a página
        window.onload = carregarHistorico;
        
         // Função para deletar um item-------------------------------------------------------------------------------------------------

        function deleteItem(id) {
            if (confirm("Tem certeza que deseja deletar este item?")) {
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "deletar_item.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        console.log("Resposta do servidor:", xhr.responseText);
                        loadTableData(); // Recarrega os dados da tabela após deletar o item
                    } else {
                        console.error("Erro na requisição:", xhr.statusText);
                    }
                };

                xhr.onerror = function() {
                    console.error("Erro de conexão.");
                };

                // Envia o ID do item a ser deletado
                let data = "action=delete&id=" + id;
                xhr.send(data);
            }
        }

        // Carrega os dados da tabela ao carregar a página
        window.onload = loadTableData;
        
  // Função para carregar os dados da tabela
    function loadTableData() {
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "carregar_dados.php", true);

    xhr.onload = function() {
        if (xhr.status === 200) {
            let data = JSON.parse(xhr.responseText);
            let tableBody = document.querySelector("#tabelaItens tbody");
            tableBody.innerHTML = "";

            const currentMonth = new Date().getMonth() + 1; // Janeiro = 1, Dezembro = 12

            data.forEach(item => {
                // Calcula o saldo se não vier do servidor
                const saldo = item.saldo || item.quantidade - 
                    (parseInt(item.janeiro || 0) + 
                     parseInt(item.fevereiro || 0) + 
                     parseInt(item.marco || 0) + 
                     parseInt(item.abril || 0) + 
                     parseInt(item.maio || 0) + 
                     parseInt(item.junho || 0) + 
                     parseInt(item.julho || 0) + 
                     parseInt(item.agosto || 0) + 
                     parseInt(item.setembro || 0) + 
                     parseInt(item.outubro || 0) + 
                     parseInt(item.novembro || 0) + 
                     parseInt(item.dezembro || 0));

                let row = `<tr data-id="${item.id}">
                    <td>${item.id}</td>
                    <td contenteditable="true" 
                        class="editable-nome"
                        onblur="saveEdit(${item.id}, 'nome', this.textContent)">${item.nome}</td>
                    <td contenteditable="true" 
                        class="editable-quantidade"
                        onblur="saveEdit(${item.id}, 'quantidade', this.textContent)">${item.quantidade}</td>
                    <td ${currentMonth === 1 ? 'data-current-month="true"' : ''}>${item.janeiro || ''}</td>
                    <td ${currentMonth === 2 ? 'data-current-month="true"' : ''}>${item.fevereiro || ''}</td>
                    <td ${currentMonth === 3 ? 'data-current-month="true"' : ''}>${item.marco || ''}</td>
                    <td ${currentMonth === 4 ? 'data-current-month="true"' : ''}>${item.abril || ''}</td>
                    <td ${currentMonth === 5 ? 'data-current-month="true"' : ''}>${item.maio || ''}</td>
                    <td ${currentMonth === 6 ? 'data-current-month="true"' : ''}>${item.junho || ''}</td>
                    <td ${currentMonth === 7 ? 'data-current-month="true"' : ''}>${item.julho || ''}</td>
                    <td ${currentMonth === 8 ? 'data-current-month="true"' : ''}>${item.agosto || ''}</td>
                    <td ${currentMonth === 9 ? 'data-current-month="true"' : ''}>${item.setembro || ''}</td>
                    <td ${currentMonth === 10 ? 'data-current-month="true"' : ''}>${item.outubro || ''}</td>
                    <td ${currentMonth === 11 ? 'data-current-month="true"' : ''}>${item.novembro || ''}</td>
                    <td ${currentMonth === 12 ? 'data-current-month="true"' : ''}>${item.dezembro || ''}</td>
                    <td data-value="${saldo}" class="saldo-cell">${saldo}</td>
                    <td><button id="delbutton" onclick="deleteItem(${item.id})"></button></td>
                </tr>`;
                tableBody.innerHTML += row;
            });
        } else {
            console.error("Erro na requisição:", xhr.statusText);
            tableBody.innerHTML = `<tr><td colspan="17" style="text-align: center; color: red;">
                Erro ao carregar dados. <button onclick="loadTableData()">Tentar novamente</button>
                </td></tr>`;
        }
    };
    xhr.onerror = function() {
        console.error("Erro de conexão.");
        let tableBody = document.querySelector("#tabelaItens tbody");
        tableBody.innerHTML = `<tr><td colspan="17" style="text-align: center; color: red;">
            Erro de conexão. Verifique sua internet e <button onclick="loadTableData()">tente novamente</button>
            </td></tr>`;
    };

    xhr.send();
}
       function saveEdit(id, field, value) {
    // Validação básica
    if (field === 'quantidade' && isNaN(value)) {
        alert("Quantidade deve ser um número!");
        return;
    }

    fetch('atualizar_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&campo=${field}&valor=${encodeURIComponent(value)}`
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error(`Resposta do servidor inválida: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            loadTableData();
            // Feedback visual
            const cell = document.querySelector(`tr[data-id="${id}"] .editable-${field}`);
            cell.style.backgroundColor = "#e6ffe6";
            setTimeout(() => cell.style.backgroundColor = "", 1000);
        } else {
            throw new Error(data.message || "Erro ao salvar");
        }
    })
    .catch(error => {
        console.error("Erro:", error);
        alert("Erro ao salvar: " + error.message);
        loadTableData();
    });
}
 function toggleDarkMode() {
            const body = document.body;
            body.classList.toggle("dark-mode");

            const themeButton = document.querySelector(".theme-button");
            if (body.classList.contains("dark-mode")) {
                themeButton.innerHTML = "<i class='fas fa-sun'></i>"; // Ícone de sol (modo claro)
                localStorage.setItem("theme", "dark");
            } else {
                themeButton.innerHTML = "<i class='fas fa-moon'></i>"; // Ícone de lua (modo escuro)
                localStorage.setItem("theme", "light");
            }
        }

        function loadTheme() {
            const savedTheme = localStorage.getItem("theme");
            const themeButton = document.querySelector(".theme-button");
            if (savedTheme === "dark") {
                document.body.classList.add("dark-mode");
                themeButton.innerHTML = "<i class='fas fa-sun'></i>"; // Ícone de sol (modo claro)
            } else {
                themeButton.innerHTML = "<i class='fas fa-moon'></i>"; // Ícone de lua (modo escuro)
            }
        }

        document.addEventListener("DOMContentLoaded", loadTheme);


        document.getElementById("btnImprimir").addEventListener("click", function () {
        window.print();
    });

function carregarItens() {
    fetch("carregar_dados.php")
        .then(response => response.json())
        .then(data => {
            let select = document.getElementById("item");
            select.innerHTML = "<option value=''>Selecione um item</option>"; // Placeholder

            data.forEach(item => {
                let option = document.createElement("option");
                option.value = item.id;
                option.textContent = item.nome;
                select.appendChild(option);
            });
        })
        .catch(error => console.error("Erro ao carregar itens:", error));
}

// Chama a função assim que a página carregar
document.addEventListener("DOMContentLoaded", carregarItens);

    // Carrega a data atual ao abrir a página
document.addEventListener('DOMContentLoaded', function() {
    const dataAtual = new Date();
    document.getElementById('data').value = dataAtual.toLocaleDateString('pt-BR');
    document.getElementById('mes').value = dataAtual.getMonth() + 1; // Janeiro = 1, Dezembro = 12
    carregarItens();
});
function carregarHistorico() {
    // Mostra indicador de carregamento
    const historico = document.getElementById("historico-entrada");
    historico.innerHTML = '<li class="loading">Carregando histórico...</li>';

    fetch("carregar_historico.php")
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Verifica se os dados são válidos
            if (!Array.isArray(data)) {
                throw new Error("Formato de dados inválido");
            }

            // Limpa o conteúdo anterior
            historico.innerHTML = "";

            // Caso não haja registros
            if (data.length === 0) {
                historico.innerHTML = '<li class="empty">Nenhuma retirada registrada ainda</li>';
                return;
            }

            // Processa cada item do histórico
            data.forEach(item => {
                const li = document.createElement("li");
                li.className = "historico-item";

                // Formatação robusta da data
                const dataExibicao = item.data_formatada_br || formatarData(item.data_retirada);
                const isDataInvalida = dataExibicao.includes('inválida') || dataExibicao.includes('não informada');

                // Montagem do HTML
                li.innerHTML = `
                    <div class="historico-cabecalho">
                        <span class="historico-data ${isDataInvalida ? 'invalid' : ''}">
                            <i class="fas fa-calendar-alt"></i> ${dataExibicao}
                        </span>
                        <span class="historico-mes">
                            <i class="fas fa-clock"></i> ${item.mes_retirada || 'Mês não especificado'}
                        </span>
                    </div>
                    <div class="historico-corpo">
                        <p><strong><i class="fas fa-box"></i> Item:</strong> ${item.item_nome || `ID ${item.item_id}`}</p>
                        <p><strong><i class="fas fa-user"></i> Solicitante:</strong> ${item.nome_solicitante || 'Não informado'}</p>
                        <p><strong><i class="fas fa-hashtag"></i> Quantidade:</strong> ${item.quantidade || '0'}</p>
                        ${item.descricao ? `<p><strong><i class="fas fa-file-alt"></i> Descrição:</strong> ${item.descricao}</p>` : ''}
                        <p><button id="delbutton" onclick="deleteHistoricoItem(${item.id})"></button></p>
                    </div>
                `;
                historico.appendChild(li);
            });
        })
        .catch(error => {
            console.error("Erro ao carregar histórico:", error);
            historico.innerHTML = `
                <li class="error">
                    <i class="fas fa-exclamation-triangle"></i> Erro ao carregar histórico
                    <p>${error.message}</p>
                    <button onclick="carregarHistorico()" class="retry-btn">
                        <i class="fas fa-sync-alt"></i> Tentar novamente
                    </button>
                </li>
            `;
        });
}

// Função auxiliar para formatar datas (mantida externa para reuso)
function formatarData(dataString) {
    if (!dataString) {
        console.warn('Data vazia recebida');
        return 'Data não informada';
    }
    
    // Se já estiver no formato dd/mm/yyyy
    if (typeof dataString === 'string' && dataString.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
        return dataString;
    }
    
    // Tenta converter de formato ISO (YYYY-MM-DD)
    if (dataString.match(/^\d{4}-\d{2}-\d{2}/)) {
        const [ano, mes, dia] = dataString.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    // Tenta converter de outros formatos
    try {
        const data = new Date(dataString);
        if (isNaN(data.getTime())) throw new Error('Data inválida');
        
        const dia = String(data.getDate()).padStart(2, '0');
        const mes = String(data.getMonth() + 1).padStart(2, '0');
        const ano = data.getFullYear();
        
        return `${dia}/${mes}/${ano}`;
    } catch (e) {
        console.error('Falha ao formatar data:', dataString, e);
        return 'Data inválida';
    }
}
// Função auxiliar para formatar data
function formatarData(dataString) {
    const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return new Date(dataString).toLocaleDateString('pt-BR', options);
}

// Função para alternar a visibilidade do histórico
function toggleHistorico() {
    const popup = document.getElementById("entrada-popup");
    popup.classList.toggle("aberto");
}

// Função para registrar nova retirada no histórico
function registrarRetirada(formData) {
    return fetch("registrar_historico.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            item_id: formData.item_id,
            nome: formData.nome,
            descricao: formData.descricao,
            quantidade: formData.quantidade,
            data: formData.data,
            mes: formData.mes
        })
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro no servidor");
        return response.json();
    });
}

// Evento de submit do formulário modificado para histórico
document.getElementById("formSolicitacao").addEventListener("submit", function(event) {
    event.preventDefault();
    
    const formData = {
        item_id: document.getElementById("item").value,
        nome: document.getElementById("nome").value,
        descricao: document.getElementById("descricao").value,
        quantidade: document.getElementById("quantidade").value,
        data: document.getElementById("data").value,
        mes: document.getElementById("mes").value
    };

    // Processa a retirada e o histórico em paralelo
    Promise.all([
        fetch("atualizar_quantidade.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `id=${formData.item_id}&quantidade=${formData.quantidade}&mes=${formData.mes}&action=retirar`
        }),
        registrarRetirada(formData)
    ])
    .then(([responseQuant, responseHist]) => Promise.all([responseQuant.json(), responseHist]))
    .then(([dataQuant, dataHist]) => {
        if (dataQuant.status === "success" && dataHist.status === "success") {
            alert("Retirada registrada com sucesso!");
            loadTableData();
            carregarHistorico();
            this.reset();
            
            // Atualiza data/mês após reset
            const dataAtual = new Date();
            document.getElementById('data').value = dataAtual.toLocaleDateString('pt-BR');
            document.getElementById('mes').value = dataAtual.getMonth() + 1;
        } else {
            throw new Error(dataQuant.message || dataHist.message || "Erro desconhecido");
        }
    })
    .catch(error => {
        console.error("Erro:", error);
        alert("Erro ao processar: " + error.message);
    });
});

// Inicialização
document.addEventListener("DOMContentLoaded", function() {
    carregarHistorico();
    
    // Botão para alternar visibilidade do histórico
    document.querySelector("#entrada-popup h2").addEventListener("click", toggleHistorico);
    
    // Atualiza o histórico a cada 30 segundos
    setInterval(carregarHistorico, 30000);
});
function formatarData(dataString) {
    // Caso a data já esteja no formato correto
    if (typeof dataString === 'string' && 
        (dataString.includes('/') || 
         dataString.includes('inválida') || 
         dataString.includes('registrada') ||
         dataString.includes('informada'))){
        return dataString;
    }
    
    // Se for null/undefined/vazio
    if (!dataString) {
        console.warn('Data vazia recebida');
        return 'Data não informada';
    }
    
    // Tenta converter de formato ISO (do banco de dados)
    if (dataString.match(/^\d{4}-\d{2}-\d{2}/)) {
        const [ano, mes, dia] = dataString.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    // Tenta converter de timestamp ou outros formatos
    try {
        const data = new Date(dataString);
        if (isNaN(data.getTime())) throw new Error('Data inválida');
        
        const dia = String(data.getDate()).padStart(2, '0');
        const mes = String(data.getMonth() + 1).padStart(2, '0');
        const ano = data.getFullYear();
        
        return `${dia}/${mes}/${ano}`;
    } catch (e) {
        console.error('Erro ao formatar data:', dataString, e);
        return 'Data inválida';
    }
}
// Slider de Imagens
document.addEventListener('DOMContentLoaded', function() {
    // Variáveis do slider
    let currentSlide = 0;
    let slides = [];
    
    // Elementos do DOM
    const slider = document.querySelector('.slider');
    const dotsContainer = document.querySelector('.slider-dots');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const openModalBtn = document.getElementById('openAddImageModal');
    const modal = document.getElementById('addImageModal');
    const closeModal = document.querySelector('.close-modal');
    const uploadForm = document.getElementById('uploadImageForm');
    const itemSelect = document.getElementById('itemSelect');
    const uploadStatus = document.getElementById('uploadStatus');
    
    // Carregar itens para o select
    function loadItemsForSelect() {
        fetch('get_items.php')
            .then(response => response.json())
            .then(data => {
                itemSelect.innerHTML = '<option value="">Selecione um item</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.nome;
                    itemSelect.appendChild(option);
                });
            });
    }
    
    // Carregar slides do banco de dados
    function loadSlides() {
        fetch('get_slides.php')
            .then(response => response.json())
            .then(data => {
                slides = data.map(slide => {
                    // Ajusta o caminho da imagem para ser relativo ao htdocs
                    if (slide.imagem_path) {
                        // Remove qualquer parte do caminho absoluto que possa estar salvo
                        const filename = slide.imagem_path.split('/').pop();
                        slide.imagem_path = 'uploads/slides/' + filename;
                    }
                    return slide;
                });
                renderSlides();
            });
    }
    
    // Renderizar slides na tela
    function renderSlides() {
        slider.innerHTML = '';
        dotsContainer.innerHTML = '';
        
        if (slides.length === 0) {
            slider.innerHTML = '<div class="no-images">Nenhuma imagem disponível</div>';
            return;
        }
        
        slides.forEach((slide, index) => {
            // Criar slide
            const slideElement = document.createElement('div');
            slideElement.className = `slide ${index === 0 ? 'active' : ''}`;
            slideElement.innerHTML = `
                <img src="${slide.imagem_path}" alt="${slide.nome}">
                <div class="slide-info">${slide.nome} - Saldo Restante: ${slide.saldo}</div>
            `;
            slider.appendChild(slideElement);
            
            // Criar dot de navegação
            const dot = document.createElement('div');
            dot.className = `dot ${index === 0 ? 'active' : ''}`;
            dot.addEventListener('click', () => goToSlide(index));
            dotsContainer.appendChild(dot);
        });
    }
    
    // Navegação do slider
    function goToSlide(index) {
        const slideElements = document.querySelectorAll('.slide');
        const dotElements = document.querySelectorAll('.dot');
        
        if (slideElements.length === 0 || dotElements.length === 0) {
            console.warn('Nenhum slide ou dot encontrado');
            return;
        }
        
        // Remove a classe 'active' do slide e dot atual
        slideElements[currentSlide]?.classList?.remove('active');
        dotElements[currentSlide]?.classList?.remove('active');
        
        // Atualiza o índice do slide atual
        currentSlide = (index + slideElements.length) % slideElements.length;
        
        // Adiciona a classe 'active' ao novo slide e dot
        slideElements[currentSlide]?.classList?.add('active');
        dotElements[currentSlide]?.classList?.add('active');
    }
    
    function prevSlide() {
        goToSlide(currentSlide - 1);
    }
    
    function nextSlide() {
        goToSlide(currentSlide + 1);
    }
    
    // Inicialização do slider
    function initSlider() {
        // Botões de navegação
        prevBtn.addEventListener('click', prevSlide);
        nextBtn.addEventListener('click', nextSlide);
        
        // Carrega os slides iniciais
        loadSlides();
    }
    
    // Modal para adicionar imagem
    openModalBtn.addEventListener('click', () => {
        modal.style.display = 'block';
        loadItemsForSelect();
    });
    
    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Envio do formulário de upload
    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const itemId = itemSelect.value;
        const fileInput = document.getElementById('imageUpload');
        
        if (!itemId || !fileInput.files[0]) {
            uploadStatus.textContent = 'Preencha todos os campos';
            uploadStatus.style.color = 'red';
            return;
        }
        
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('imagem', fileInput.files[0]);
        
        uploadStatus.textContent = 'Enviando...';
        uploadStatus.style.color = 'blue';
        
        try {
            const response = await fetch('get_slide.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Erro no servidor');
            }
            
            const data = await response.json();
            
            if (data.status !== 'success') {
                throw new Error(data.message || 'Erro no processamento');
            }
            
            uploadStatus.textContent = data.message || 'Imagem enviada com sucesso!';
            uploadStatus.style.color = 'green';
            
            // Recarregar slides após 1,5 segundos
            setTimeout(() => {
                loadSlides();
                modal.style.display = 'none';
                uploadStatus.textContent = '';
                uploadForm.reset();
            }, 1500);
            
        } catch (error) {
            console.error('Erro no upload:', error);
            uploadStatus.textContent = 'Erro: ' + error.message;
            uploadStatus.style.color = 'red';
        }
    });
    
    // Inicializa o slider quando o DOM estiver pronto
    initSlider();
});
</script>
</body>
</html>
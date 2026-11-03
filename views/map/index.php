<?php
/**
 * VITÓRIA INTELIGÊNCIA — Mapa Interativo GIS de Vitória/ES (Módulo 4)
 *
 * Recursos implementados:
 * - Leaflet + OpenStreetMap (ruas e avenidas de Vitória/ES)
 * - Limites poligonais GeoJSON verificados dos 80 bairros oficiais (Lei Municipal nº 8.611/2013)
 * - Pesquisa de endereços (Nominatim OpenStreetMap restrito a Vitória/ES + filtro de bairros)
 * - Georreferenciamento interativo ao clicar no mapa (com botão direto para abrir demanda naquela coordenada)
 * - Camadas selecionáveis:
 *   1. Limites dos 80 Bairros
 *   2. Mapa de Calor / Densidade de Demandas por Bairro
 *   3. Unidades de Saúde (29 UBS + 2 PAs)
 *   4. Escolas Municipais (EMEFs e CMEIs)
 *   5. Parques Urbanos e Naturais
 *   6. Praças Públicas
 *   7. Equipamentos Esportivos e Administrativos
 *   8. Demandas Comunitárias Georreferenciadas
 * - Painel lateral de indicadores detalhados ao clicar em qualquer bairro
 */
?>

<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="vi-card p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="addressSearchInput" class="form-control" placeholder="Pesquisar rua, avenida ou endereço em Vitória/ES...">
                        <button type="button" class="btn btn-primary" onclick="searchAddressVitoria()">Buscar</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="neighborhoodQuickSelect" class="form-select form-select-sm" onchange="focusNeighborhoodById(this.value)">
                        <option value="">Ir para um dos 80 Bairros Oficiais...</option>
                        <?php foreach ($neighborhoodsStats as $n): ?>
                            <option value="<?= (int)$n['id'] ?>">
                                <?= e($n['name']) ?> (<?= e($n['region_name']) ?>) — <?= (int)$n['demands_count'] ?> dem.
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1 justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="resetVitoriaMapView()">
                        <i class="bi bi-arrows-fullscreen me-1"></i>Visão Geral
                    </button>
                </div>
            </div>

            <!-- Controle Rápido de Camadas -->
            <div class="d-flex flex-wrap gap-3 mt-3 pt-2 border-top small">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleBairros" checked onchange="toggleMapLayer('bairros', this.checked)">
                    <label class="form-check-label fw-semibold" for="toggleBairros">Limites dos 80 Bairros</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleHeatmap" checked onchange="toggleMapLayer('heatmap', this.checked)">
                    <label class="form-check-label fw-semibold text-danger" for="toggleHeatmap">Mapa de Calor (Demandas)</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleDemands" checked onchange="toggleMapLayer('demands', this.checked)">
                    <label class="form-check-label fw-semibold text-warning-emphasis" for="toggleDemands">Marcadores de Demandas</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleSaude" checked onchange="toggleMapLayer('saude', this.checked)">
                    <label class="form-check-label" for="toggleSaude"><span class="badge bg-danger">UBS/Saúde</span></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleEscolas" checked onchange="toggleMapLayer('escolas', this.checked)">
                    <label class="form-check-label" for="toggleEscolas"><span class="badge bg-primary">Escolas</span></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleParques" checked onchange="toggleMapLayer('parques', this.checked)">
                    <label class="form-check-label" for="toggleParques"><span class="badge bg-success">Parques & Praças</span></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleEsporte" checked onchange="toggleMapLayer('esporte', this.checked)">
                    <label class="form-check-label" for="toggleEsporte"><span class="badge bg-dark">Esporte & Órgãos</span></label>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="vi-card p-3 h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fw-bold small text-uppercase text-primary"><i class="bi bi-crosshair me-1"></i>Georreferenciamento Ativo</span>
                <span class="badge bg-light text-dark border" id="clickedCoordsBadge">-20.3155, -40.3128</span>
            </div>
            <div class="small text-muted mb-2" id="searchStatusMsg">
                Clique em qualquer ponto do mapa ou polígono de bairro para inspecionar indicadores ou abrir demanda georreferenciada.
            </div>
            <a href="index.php?route=demands" id="btnCreateDemandAtCoord" class="btn btn-sm btn-success">
                <i class="bi bi-plus-circle me-1"></i>Registrar Demanda nas Coordenadas Selecionadas
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Mapa Leaflet -->
    <div class="col-lg-8">
        <div class="vi-card p-2">
            <div id="vitoriaMap"></div>
        </div>
    </div>

    <!-- Painel de Indicadores do Bairro Selecionado -->
    <div class="col-lg-4">
        <div class="vi-card h-100 d-flex flex-column">
            <div class="vi-card-header d-flex justify-content-between align-items-center" style="background-color: #101B33; color: #fff;">
                <div>
                    <span class="badge bg-primary mb-1" id="panelRegionBadge">Município de Vitória / ES</span>
                    <h5 class="fw-bold mb-0" id="panelNeighborhoodName">Selecione um Bairro no Mapa</h5>
                </div>
                <i class="bi bi-geo-fill fs-3 text-info"></i>
            </div>

            <div class="p-3 flex-grow-1">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small">População Estimada</div>
                            <div class="fw-bold fs-5" id="panelPop">322.869</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small">Área Territorial</div>
                            <div class="fw-bold fs-5" id="panelArea">97,12 km²</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small">Domicílios</div>
                            <div class="fw-bold fs-5" id="panelHouseholds">124.500</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small">Equipamentos Públicos</div>
                            <div class="fw-bold fs-5 text-primary" id="panelEquips"><?= count($equipments) ?></div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold small text-uppercase text-secondary border-bottom pb-2">Indicadores Comunitários do Bairro</h6>
                <ul class="list-group list-group-flush small mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Demandas Comunitárias Totais</span>
                        <span class="badge bg-primary rounded-pill" id="panelDemandsTotal"><?= count($demands) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Demandas em Aberto / Execução</span>
                        <span class="badge bg-warning text-dark rounded-pill" id="panelDemandsOpen">
                            <?= count(array_filter($demands, fn($d) => $d['status'] !== 'Concluída')) ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Demandas Concluídas</span>
                        <span class="badge bg-success rounded-pill" id="panelDemandsResolved">
                            <?= count(array_filter($demands, fn($d) => $d['status'] === 'Concluída')) ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Contatos Cadastrados (LGPD)</span>
                        <span class="badge bg-info text-dark rounded-pill" id="panelContactsCount"><?= (int)$totalConsentedContacts ?></span>
                    </li>
                </ul>

                <h6 class="fw-bold small text-uppercase text-secondary border-bottom pb-2">Equipamentos e Demandas no Bairro</h6>
                <div id="panelNeighborhoodDetails" class="small text-muted" style="max-height: 220px; overflow-y: auto;">
                    Clique sobre qualquer um dos 80 bairros delimitados no mapa para listar imediatamente suas Unidades de Saúde, Escolas, Parques e Demandas Comunitárias.
                </div>
            </div>

            <div class="p-3 border-top bg-light small text-muted">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                Fonte Cartográfica: Lei Municipal nº 8.611/2013 (PMV / Geobases-ES / OpenStreetMap).
            </div>
        </div>
    </div>
</div>

<script>
const neighborhoodsStats = <?= json_encode($neighborhoodsStats, JSON_UNESCAPED_UNICODE) ?>;
const equipmentsData = <?= json_encode($equipments, JSON_UNESCAPED_UNICODE) ?>;
const demandsData = <?= json_encode($demands, JSON_UNESCAPED_UNICODE) ?>;

let map, bairrosLayer, heatLayer, demandsLayer, saudeLayer, escolasLayer, parquesLayer, esporteLayer, geocoderMarker;
const statsById = {};
neighborhoodsStats.forEach(n => { statsById[n.id] = n; });

document.addEventListener('DOMContentLoaded', function () {
    map = L.map('vitoriaMap').setView([-20.2975, -40.3128], 13);

    // Camada Base OpenStreetMap (Ruas e Avenidas de Vitória/ES)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors | Prefeitura de Vitória / Geobases ES'
    }).addTo(map);

    demandsLayer = L.layerGroup().addTo(map);
    saudeLayer   = L.layerGroup().addTo(map);
    escolasLayer = L.layerGroup().addTo(map);
    parquesLayer = L.layerGroup().addTo(map);
    esporteLayer = L.layerGroup().addTo(map);

    // 1. Carregar GeoJSON Oficial dos 80 Bairros de Vitória/ES
    fetch('public/assets/vitoria_bairros.geojson')
        .then(res => res.json())
        .then(geoData => {
            bairrosLayer = L.geoJSON(geoData, {
                style: function (feature) {
                    const st = statsById[feature.properties.id] || { demands_count: 0 };
                    const cnt = parseInt(st.demands_count || 0, 10);
                    let fillColor = '#2563EB';
                    let fillOpacity = 0.18;
                    if (cnt >= 2) {
                        fillColor = '#DC2626';
                        fillOpacity = 0.38;
                    } else if (cnt === 1) {
                        fillColor = '#F59E0B';
                        fillOpacity = 0.30;
                    }
                    return {
                        color: '#101B33',
                        weight: 1.5,
                        fillColor: fillColor,
                        fillOpacity: fillOpacity
                    };
                },
                onEachFeature: function (feature, layer) {
                    const p = feature.properties;
                    const st = statsById[p.id] || { demands_count: 0, contacts_count: 0 };
                    layer.bindTooltip(
                        `<strong>${p.nome}</strong><br>${p.regiao}<br>Demandas: ${st.demands_count} | Contatos LGPD: ${st.contacts_count}`,
                        { sticky: true }
                    );
                    layer.on('click', function (e) {
                        selectNeighborhood(p.id);
                        updateSelectedCoords(e.latlng.lat, e.latlng.lng, p.id);
                    });
                }
            }).addTo(map);
        });

    // 2. Construir Mapa de Calor de Demandas Agregadas por Bairro
    const heatPoints = [];
    demandsData.forEach(d => {
        const weight = d.priority === 'Urgente' ? 1.0 : (d.priority === 'Alta' ? 0.8 : 0.5);
        heatPoints.push([parseFloat(d.lat), parseFloat(d.lng), weight]);
    });
    neighborhoodsStats.forEach(n => {
        if (parseInt(n.demands_count, 10) > 0) {
            heatPoints.push([parseFloat(n.lat), parseFloat(n.lng), Math.min(1.0, parseInt(n.demands_count, 10) * 0.45)]);
        }
    });
    if (typeof L.heatLayer === 'function') {
        heatLayer = L.heatLayer(heatPoints, {
            radius: 28,
            blur: 20,
            maxZoom: 16,
            gradient: { 0.3: '#2563EB', 0.65: '#F59E0B', 1.0: '#DC2626' }
        }).addTo(map);
    }

    // 3. Plotar Equipamentos Públicos por Tipo
    equipmentsData.forEach(eq => {
        const lat = parseFloat(eq.lat);
        const lng = parseFloat(eq.lng);
        let badgeColor = '#2563EB';
        let targetGroup = esporteLayer;

        if (eq.type === 'Unidade de Saúde') {
            badgeColor = '#DC2626';
            targetGroup = saudeLayer;
        } else if (eq.type === 'Escola') {
            badgeColor = '#2563EB';
            targetGroup = escolasLayer;
        } else if (eq.type === 'Parque' || eq.type === 'Praça') {
            badgeColor = '#16A34A';
            targetGroup = parquesLayer;
        }

        const marker = L.circleMarker([lat, lng], {
            radius: 7,
            fillColor: badgeColor,
            color: '#FFFFFF',
            weight: 2,
            fillOpacity: 0.95
        });

        marker.bindPopup(`
            <div style="min-width:210px">
                <span class="badge mb-1" style="background:${badgeColor}">${eq.type}</span>
                <div class="fw-bold">${eq.name}</div>
                <div class="small text-muted mt-1"><i class="bi bi-geo-alt"></i> ${eq.address} — ${eq.neighborhood_name}</div>
                ${eq.phone ? `<div class="small mt-1"><i class="bi bi-telephone"></i> ${eq.phone}</div>` : ''}
            </div>
        `);
        marker.addTo(targetGroup);
    });

    // 4. Plotar Demandas Comunitárias
    demandsData.forEach(d => {
        const lat = parseFloat(d.lat);
        const lng = parseFloat(d.lng);
        const color = d.status === 'Concluída' ? '#16A34A' : (d.priority === 'Urgente' ? '#DC2626' : '#F59E0B');

        const marker = L.circleMarker([lat, lng], {
            radius: 9,
            fillColor: color,
            color: '#101B33',
            weight: 2,
            fillOpacity: 0.95
        });

        marker.bindPopup(`
            <div style="min-width:230px">
                <span class="badge bg-dark mb-1">${d.protocol_code}</span>
                <span class="badge bg-warning text-dark mb-1">${d.category}</span>
                <div class="fw-bold">${d.title}</div>
                <div class="small text-muted mt-1">${d.address_location} (${d.neighborhood_name})</div>
                <div class="small mt-1"><strong>Prioridade:</strong> ${d.priority} | <strong>Status:</strong> ${d.status}</div>
                <div class="mt-2">
                    <a href="index.php?route=demands&highlight=${d.id}" class="btn btn-sm btn-primary w-100 text-white">Ver Detalhes</a>
                </div>
            </div>
        `);
        marker.addTo(demandsLayer);
    });

    // Georreferenciamento ao clicar em qualquer ponto do mapa
    map.on('click', function (e) {
        updateSelectedCoords(e.latlng.lat, e.latlng.lng, null);
    });
});

function updateSelectedCoords(lat, lng, neighborhoodId) {
    const latFixed = lat.toFixed(6);
    const lngFixed = lng.toFixed(6);
    document.getElementById('clickedCoordsBadge').textContent = `${latFixed}, ${lngFixed}`;
    let url = `index.php?route=demands&new_lat=${latFixed}&new_lng=${lngFixed}`;
    if (neighborhoodId) {
        url += `&neighborhood_id=${neighborhoodId}`;
    }
    document.getElementById('btnCreateDemandAtCoord').href = url;
}

function selectNeighborhood(id) {
    const st = statsById[id];
    if (!st) return;

    document.getElementById('panelRegionBadge').textContent = st.region_name;
    document.getElementById('panelNeighborhoodName').textContent = st.name;
    document.getElementById('panelPop').textContent = parseInt(st.population, 10).toLocaleString('pt-BR');
    document.getElementById('panelArea').textContent = parseFloat(st.area_km2).toFixed(2).replace('.', ',') + ' km²';
    document.getElementById('panelHouseholds').textContent = parseInt(st.households, 10).toLocaleString('pt-BR');
    document.getElementById('panelEquips').textContent = st.equipments_count;
    document.getElementById('panelDemandsTotal').textContent = st.demands_count;
    document.getElementById('panelDemandsOpen').textContent = st.open_demands_count;
    document.getElementById('panelDemandsResolved').textContent = st.resolved_demands_count;
    document.getElementById('panelContactsCount').textContent = st.contacts_count;

    const eqList = equipmentsData.filter(e => parseInt(e.neighborhood_id, 10) === parseInt(id, 10));
    const dmList = demandsData.filter(d => parseInt(d.neighborhood_id, 10) === parseInt(id, 10));

    let html = '';
    if (dmList.length > 0) {
        html += '<div class="fw-bold text-dark mb-1">Demandas Registradas:</div><ul class="ps-3 mb-2">';
        dmList.forEach(d => {
            html += `<li><strong>${d.protocol_code}</strong>: ${d.title} (${d.status})</li>`;
        });
        html += '</ul>';
    } else {
        html += '<div class="mb-2">Nenhuma demanda pendente neste bairro.</div>';
    }

    if (eqList.length > 0) {
        html += '<div class="fw-bold text-dark mb-1">Equipamentos Públicos:</div><ul class="ps-3 mb-0">';
        eqList.forEach(e => {
            html += `<li>[${e.type}] ${e.name}</li>`;
        });
        html += '</ul>';
    }
    document.getElementById('panelNeighborhoodDetails').innerHTML = html;
}

function focusNeighborhoodById(id) {
    if (!id) return;
    const st = statsById[id];
    if (!st) return;
    map.setView([parseFloat(st.lat), parseFloat(st.lng)], 15);
    selectNeighborhood(parseInt(id, 10));
    updateSelectedCoords(parseFloat(st.lat), parseFloat(st.lng), parseInt(id, 10));
}

function resetVitoriaMapView() {
    map.setView([-20.2975, -40.3128], 13);
}

function toggleMapLayer(layerName, visible) {
    const mapLayers = {
        bairros: bairrosLayer,
        heatmap: heatLayer,
        demands: demandsLayer,
        saude: saudeLayer,
        escolas: escolasLayer,
        parques: parquesLayer,
        esporte: esporteLayer
    };
    const target = mapLayers[layerName];
    if (!target) return;
    if (visible) {
        map.addLayer(target);
    } else {
        map.removeLayer(target);
    }
}

function searchAddressVitoria() {
    const q = document.getElementById('addressSearchInput').value.trim();
    if (!q) return;
    const statusEl = document.getElementById('searchStatusMsg');
    statusEl.textContent = 'Buscando endereço em Vitória/ES...';

    // 1. Verificar primeiro se é o nome de um dos 80 bairros oficiais
    const lowerQ = q.toLowerCase();
    const matchedBairro = neighborhoodsStats.find(n => n.name.toLowerCase().includes(lowerQ));
    if (matchedBairro) {
        focusNeighborhoodById(matchedBairro.id);
        statusEl.textContent = `Bairro localizado: ${matchedBairro.name} (${matchedBairro.region_name}).`;
        return;
    }

    // 2. Consultar geocodificador Nominatim OpenStreetMap restrito a Vitória/ES
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q + ', Vitória, Espírito Santo, Brasil')}&limit=1`;
    fetch(url)
        .then(r => r.json())
        .then(results => {
            if (results && results.length > 0) {
                const lat = parseFloat(results[0].lat);
                const lon = parseFloat(results[0].lon);
                map.setView([lat, lon], 16);
                if (geocoderMarker) map.removeLayer(geocoderMarker);
                geocoderMarker = L.marker([lat, lon]).addTo(map)
                    .bindPopup(`<strong>Endereço Localizado:</strong><br>${results[0].display_name}`).openPopup();
                updateSelectedCoords(lat, lon, null);
                statusEl.textContent = `Localizado: ${results[0].display_name}`;
            } else {
                statusEl.textContent = 'Endereço não encontrado. Tente informar o nome da rua ou selecione o bairro na lista.';
            }
        })
        .catch(() => {
            statusEl.textContent = 'Busca online indisponível no momento. Utilize o seletor dos 80 bairros ao lado.';
        });
}
</script>

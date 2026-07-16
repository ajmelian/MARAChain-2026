# MARAChain — HowTo: Instalación y Configuración de Nodo IPFS

**Versión:** 1.0  
**Fecha:** 2026-07-16  
**Aplica a:** Fase 2 del roadmap (IPFS Privado)

---

## Requisitos

- Servidor Linux (Ubuntu 22.04+ o Debian 12+)
- Go 1.20+ instalado (`go version`)
- Usuario `www-data` o el usuario de servicio de MARAChain
- Acceso a `curl` para probar la API
- Hostname o IP fija para el nodo

---

## 1. Instalar IPFS (Kubo)

```bash
# Descargar la última versión estable de Kubo
wget -q "https://dist.ipfs.tech/kubo/v0.32.0/kubo_v0.32.0_linux-amd64.tar.gz"

# Extraer
tar -xzf kubo_v0.32.0_linux-amd64.tar.gz

# Instalar
cd kubo
sudo bash install.sh

# Verificar
ipfs version

# Limpiar
cd .. && rm -rf kubo kubo_v0.32.0_linux-amd64.tar.gz
```

---

## 2. Inicializar el repositorio

```bash
# Crear usuario de servicio si no existe
sudo useradd -r -s /bin/false -m -d /var/lib/marachain marachain

# Inicializar IPFS (como usuario marachain)
sudo -u marachain ipfs init --profile server

# El comando anterior crea ~/.ipfs (apuntado a /var/lib/marachain/.ipfs)
```

---

## 3. Configurar el nodo para MARAChain

### 3.1. Generar swarm key privada

La `swarm.key` asegura que solo los nodos autorizados se conecten a la red.

```bash
# Generar swarm.key (64 bytes aleatorios)
sudo -u marachain bash -c '
cat > /var/lib/marachain/.ipfs/swarm.key << "EOF"
/key/swarm/psk/1.0.0/
/base16/
'$(openssl rand -hex 64)'
EOF
'
```

**Backup obligatorio**: guarda esta clave en un lugar seguro fuera del servidor.
Sin ella, no se pueden unir nuevos nodos a la red MARAChain.

### 3.2. Configurar API y Gateway

```bash
sudo -u marachain ipfs config --json API.HTTPHeaders.Access-Control-Allow-Origin '["http://127.0.0.1:8080"]'
sudo -u marachain ipfs config Addresses.API /ip4/127.0.0.1/tcp/5001
sudo -u marachain ipfs config Addresses.Gateway /ip4/127.0.0.1/tcp/8080
sudo -u marachain ipfs config --json Swarm.DisableNatPortMap true
```

### 3.3. Deshabilitar recursos innecesarios

```bash
sudo -u marachain ipfs config --json Experimental.FilestoreEnabled false
sudo -u marachain ipfs config --json Experimental.UrlstoreEnabled false
sudo -u marachain ipfs config --json Reprovider.Interval '0'
```

---

## 4. Configurar límites de recursos

```bash
sudo -u marachain ipfs config Datastore.StorageMax 50GB
sudo -u marachain ipfs config --json Datastore.GCPeriod '"1h"'
sudo -u marachain ipfs config --json Swarm.ConnMgr.HighWater 900
sudo -u marachain ipfs config --json Swarm.ConnMgr.LowWater 400
```

---

## 5. Servicio systemd para IPFS

Crear `/etc/systemd/system/ipfs.service`:

```ini
[Unit]
Description=IPFS Daemon — MARAChain Private Network
Documentation=https://docs.ipfs.tech
After=network.target

[Service]
Type=simple
User=marachain
Group=marachain
ExecStart=/usr/local/bin/ipfs daemon --enable-gc
Restart=on-failure
RestartSec=10
LimitNOFILE=65536
AmbientCapabilities=CAP_NET_BIND_SERVICE

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable ipfs
sudo systemctl start ipfs
sudo systemctl status ipfs --no-pager
```

---

## 6. Verificar el nodo

```bash
# Verificar que la API responde
curl -s -X POST http://127.0.0.1:5001/api/v0/id | jq .

# Salida esperada (parcial)
# {
#   "ID": "12D3KooW...",
#   "PublicKey": "...",
#   "Addresses": ["/ip4/0.0.0.0/tcp/4001/p2p/12D3KooW..."],
#   "AgentVersion": "kubo/0.32.0/"
# }

# Verificar peers conectados
curl -s -X POST http://127.0.0.1:5001/api/v0/swarm/peers | jq '.Peers | length'

# Probar subida
echo "MARAChain test" > /tmp/marachain-test.txt
HASH=$(curl -s -F "file=@/tmp/marachain-test.txt" http://127.0.0.1:5001/api/v0/add | jq -r '.Hash')
echo "CID: $HASH"

# Verificar pin
curl -s -X POST "http://127.0.0.1:5001/api/v0/pin/ls?arg=$HASH" | jq '.'

# Leer el archivo de vuelta
curl -s "http://127.0.0.1:8080/ipfs/$HASH"

# Limpiar
rm /tmp/marachain-test.txt
curl -s -X POST "http://127.0.0.1:5001/api/v0/pin/rm?arg=$HASH" | jq '.'
```

---

## 7. Conectar a otros nodos MARAChain

Cada nodo MARAChain tiene una dirección pública (ej: `/ip4/203.0.113.10/tcp/4001/p2p/12D3KooW...`).

```bash
# Conectar a otro nodo
sudo -u marachain ipfs swarm connect /ip4/<IP_DEL_NODO>/tcp/4001/p2p/<PEER_ID>

# Verificar conexión
curl -s -X POST http://127.0.0.1:5001/api/v0/swarm/peers | jq '.Peers | length'
# Debería mostrar al menos 1 peer conectado
```

Para hacer la conexión permanente, añadir el peer al bootstrap list:

```bash
sudo -u marachain ipfs bootstrap add /ip4/<IP_DEL_NODO>/tcp/4001/p2p/<PEER_ID>
```

---

## 8. Verificación desde StorageService

```bash
# Probar el health check de MARAChain
curl -s http://localhost/health | jq '.checks.ipfs'

# Salida esperada:
# {
#   "ipfs": "connected",
#   "ipfs_peer_id": "12D3KooW..."
# }
```

Para probar el StorageService directamente:

```bash
# Generar contenido de prueba cifrado
CIPHER=$(echo "MARAChain test content" | base64)

# Subir via PHP CLI
cd /var/www/prod/current && php -r '
$storage = new \App\Services\StorageService();
$result = $storage->uploadToIpfs(base64_decode("'"$CIPHER"'"));
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
'
```

---

## 9. Resolución de problemas

| Problema | Causa | Solución |
|----------|-------|----------|
| `curl IPFS error: Connection refused` | IPFS no iniciado | `sudo systemctl start ipfs && sudo journalctl -u ipfs --no-pager -n 20` |
| `curl IPFS error: 404` | API URL incorrecta | Verificar `ipfs config Addresses.API` |
| `pin/ls returns 500` | CID no existe o corrupto | `ipfs pin verify` para diagnosticar |
| No hay peers conectados | Firewall bloquea puerto 4001 | Abrir puerto 4001 en el firewall |
| `swarm.key mismatch` | Clave privada incorrecta | Todos los nodos deben tener la misma `swarm.key` |
| Disco lleno | GC no corre lo suficiente | `ipfs repo gc` manual o reducir `StorageMax` |
| Subida lenta | Archivos muy grandes | MVP solo permite PDF < 50MB |

---

## 10. Próximos pasos (escalado multi-nodo)

Cuando se requiera alta disponibilidad:

1. Repetir pasos 1-4 en un segundo servidor (otra ubicación UE)
2. Copiar `swarm.key` al nuevo nodo
3. Conectar ambos nodos (paso 7)
4. Configurar replicación: `ipfs pin add --recursive` en ambos nodos
5. El worker `ipfs:reconcile` mantendrá la consistencia

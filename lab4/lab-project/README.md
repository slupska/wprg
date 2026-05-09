# 🐳 Docker Dev Stack – lokalny zamiennik XAMPP

Prosty, gotowy do uruchomienia stos deweloperski oparty o Docker Compose.  
Zastępuje XAMPP w codziennej pracy z PHP i MySQL.

---

## 📋 Wymagania wstępne

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows / macOS / Linux)
- Docker Compose v2 (wbudowany w Docker Desktop)
- Wolne porty: **8080**, **8081**, **3307**

---

## 📁 Struktura katalogów

```
lab-project/
├── src/
│   └── index.php          # Kod aplikacji PHP (tu piszesz swój projekt)
├── docker-compose.yml     # Definicja usług Docker
├── Dockerfile             # Obraz PHP 8.2 + Apache
├── .env.example           # Przykładowe zmienne środowiskowe
├── .env                   # Twoje lokalne hasła (NIE commituj!)
├── .gitignore
└── README.md
```

---

## 🚀 Uruchomienie krok po kroku

### 1. Sklonuj lub skopiuj projekt

```bash
cd lab-project
```

### 2. Utwórz plik `.env` z danymi logowania

```bash
# Windows (cmd)
copy .env.example .env

# Windows (PowerShell) / macOS / Linux
cp .env.example .env
```

> Możesz zmienić hasła w pliku `.env` – domyślne wartości działają od razu.

### 3. Uruchom wszystkie kontenery

```bash
docker compose up -d --build
```

Pierwsze uruchomienie pobierze obrazy i zbuduje kontener PHP (~1–3 min).  
Kolejne starty będą znacznie szybsze.

### 4. Sprawdź czy wszystko działa

Otwórz w przeglądarce:

| Usługa     | Adres                    |
|------------|--------------------------|
| Aplikacja  | http://localhost:8080    |
| phpMyAdmin | http://localhost:8081    |

---

## 🛠️ Przydatne komendy

| Komenda | Opis |
|---------|------|
| `docker compose up -d --build` | Uruchom wszystko (z przebudowaniem obrazu) |
| `docker compose up -d` | Uruchom bez przebudowywania |
| `docker compose down` | Zatrzymaj kontenery (dane MySQL zostają) |
| `docker compose down -v` | Zatrzymaj i **usuń dane MySQL** (volume) |
| `docker compose logs -f` | Śledź logi wszystkich kontenerów na żywo |
| `docker compose logs -f app` | Logi tylko kontenera PHP/Apache |
| `docker compose logs -f db` | Logi tylko MySQL |
| `docker compose ps` | Status kontenerów |
| `docker compose restart app` | Restart kontenera aplikacji |

---

## 🔑 Dane logowania

### Baza danych MySQL

| Parametr    | Wartość          |
|-------------|------------------|
| Host        | `localhost`      |
| Port        | `3307`           |
| Baza danych | `devdb`          |
| Użytkownik  | `devuser`        |
| Hasło       | `devpassword`    |
| Root hasło  | `rootpassword`   |

> **Połączenie wewnątrz kontenerów** (np. z PHP): host = `db`, port = `3306`

### phpMyAdmin

| Parametr   | Wartość         |
|------------|-----------------|
| URL        | http://localhost:8081 |
| Użytkownik | `root`          |
| Hasło      | `rootpassword`  |

---

## ✅ Jak sprawdzić, że wszystko działa

1. **Aplikacja PHP** – wejdź na http://localhost:8080  
   → Powinieneś zobaczyć kartę ze statusem z zielonymi znacznikami **OK** przy PHP i MySQL.

2. **phpMyAdmin** – wejdź na http://localhost:8081  
   → Zaloguj się jako `root` / `rootpassword`, powinieneś zobaczyć bazę `devdb`.

3. **Status kontenerów** – uruchom w terminalu:
   ```bash
   docker compose ps
   ```
   → Wszystkie trzy kontenery (`dev_app`, `dev_db`, `dev_phpmyadmin`) powinny mieć status `running`.

---

## ❗ Najczęstsze problemy

### Kontener `db` nie startuje lub nie jest gotowy
```bash
docker compose logs db
```
Odczekaj 20–30 sekund po uruchomieniu – MySQL potrzebuje chwili na inicjalizację.  
Kontener `app` poczeka na gotowość bazy dzięki `healthcheck`.

---

### Port jest już zajęty (`address already in use`)
Zmień port w `docker-compose.yml`, np.:
```yaml
ports:
  - "9080:80"   # zamiast 8080:80
```
Lub zatrzymaj aplikację korzystającą z tego portu.

---

### Błąd połączenia z bazą w aplikacji PHP
Upewnij się, że:
- host w PHP to `db` (nie `localhost`),
- hasła w `.env` zgadzają się z tym, co wpisałeś,
- kontener `db` ma status `healthy`: `docker compose ps`.

---

### Chcę wyczyścić wszystko i zacząć od nowa
```bash
docker compose down -v
docker compose up -d --build
```
> ⚠️ Komenda `down -v` **usuwa wszystkie dane z bazy MySQL**.

---

### Zmiany w `src/` nie są widoczne
Katalog `./src` jest montowany na żywo — odśwież przeglądarkę, zmiany powinny być widoczne od razu.  
Jeśli modyfikujesz `Dockerfile`, musisz przebudować obraz: `docker compose up -d --build`.

---

## 📝 Dalszy rozwój

- Dodaj kolejne pliki PHP do katalogu `src/` – są dostępne od razu.
- Zainstaluj Composer wewnątrz kontenera: `docker compose exec app composer install`
- Importuj bazę danych przez phpMyAdmin (http://localhost:8081) lub komendą:
  ```bash
  docker compose exec -T db mysql -u devuser -pdevpassword devdb < dump.sql
  ```


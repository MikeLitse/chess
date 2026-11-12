# chess
This is an RESTful API that serves a chess game where two human players can play with each other

## API ENDPOINTS
| Method | URL | Payload / Body | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/chess` | — | Diagnostics |
| `POST` | `/chess/auth/login` | `{"user":"string","pass":"string"}` | Login |
| `POST` | `/chess/users` | `{"user":"string","pass":"string"}` | Register |
| `GET` | `/chess/users/{id}` | — | Get User Info |
| `PATCH` | `/chess/users/{id}` | `{"user":"string", ...}` | Update User |
| `DELETE` | `/chess/users/{id}` | — | Delete User |
| `GET` | `/chess/games` | — | Get Joinable Games |
| `POST` | `/chess/games` | `{"id":1,"color":"white"}` | Create a new game |
| `GET` | `/chess/games/{id}` | — | Get a single game |
| `PATCH` | `/chess/games/{id}` | `{"userid":2}` | Join a game |
| `POST` | `/chess/play` | `{"userid": 1, "from":"e2","to":"e4"}` | Play move |
| `PATCH` | `/chess/play/{userId}` | `{"action":"resign"}` | Player resign |
| `GET` | `/chess/matchHistory` | — | Get Match History |
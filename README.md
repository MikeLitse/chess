# chess
This is an RESTful API that serves a chess game where two human players can play with each other

## API ENDPOINTS
| Method | URL | Payload / Body | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | — | Diagnostics |
| `POST` | `/auth/login` | `{"user":"string","pass":"string"}` | Login |
| `POST` | `/users` | `{"user":"string","pass":"string"}` | Register |
| `GET` | `/users/{id}` | — | Get User Info |
| `PATCH` | `/users/{id}` | `{"user":"string", ...}` | Update User |
| `DELETE` | `/users/{id}` | — | Delete User |
| `GET` | `/games` | — | Get Joinable Games |
| `POST` | `/games` | `{"id":1,"color":"white"}` | Create a new game |
| `GET` | `/games/{id}` | — | Get a single game |
| `PATCH` | `/games/{id}` | `{"userid":2}` | Join a game |
| `POST` | `/play` | `{"userid": 1, "from":"e2","to":"e4"}` | Play move |
| `PATCH` | `/play/{userId}` | `{"action":"resign"}` | Player resign |
| `*` | `/matchHistory` | — | *TODO: MATCH HISTORY* |
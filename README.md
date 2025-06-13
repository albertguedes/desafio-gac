# Desafio para full stack Grupo Adriano Cobuccio

# Tecnologias

Para esse projeto, foram utilizadas as seguintes tecnologias:

    * Docker 28.0.1
    * Nginx 1.24.0
    * PHP-FPM 8.1.19
    * PHP 8.1.19  
    * Laravel 10.10
    * PostgreSQL 17.5

Elas todas fazem parte de um projeto que eu tinha desenvolvido, o lapong-docker - [https://github.com/albertguedes/lapong-docker](https://github.com/albertguedes/lapong-docker) - que
 é um docker compose voltado para desenvolvimento em laravel e que usei em 
 vários projetos.

# Instalação

Para esse projeto, clone o repositorio:

```bash
$ git clone https://github.com/albertguedes/desafio-gac
```

Vá na pasta clonada, e execute o docker-compose up para criar os containers e 
imagens:


```bash
$ docker compose up --build
```

Na pasta 'src' possui o código fonte do projeto, onde existe o script 'dockli' 
que serve para acessar o sistema sem precisar entrar no container.
Com ele, gere a base de dados 

```bash
desafio-gac/src$ ./dockli php artisan migrate:fresh --seed
```

Após isso, abra o navegador e acesse a url:

```
http://localhost:8080
```

Na base de dados criada, foi criado um usuário padrão para login.
As credenciais desse usuário para login são:

```
- email: jose@fakemail.com
- senha: jose@fakemail.com
```

# Utilização

Após o login, será redirecionado para a tela de perfil do usuário, com nome e 
email.

No menu, o link "Wallet" leva á tela de transações, onde aparece 

- o total na conta (balance)
- os menus de depósito e transferencia
- e a lista de transações.

# Depósito

Para realizar o depósito, basta digitar uma quantia em moeda com dois 
dígitos (xxxx.xx) e clicar em "Depositar".

Depois de realizar o depósito, o total na conta será atualizado e deverá aparecer
uma nova transação na lista de transações.

# Transferência

Para realizar a transferência, selecione um dos usuários criados na base de dados
e que simulam usuários da plataforma, e digite uma quantia em moeda com dois 
dígitos (xxxx.xx) e clicar em "Transferir".

Depois de realizar a transferência, o total na conta do usuário selecionado 
será atualizado e deverá aparecer uma nova transação na lista de transações.

# Reverter operação

Para reverter uma transação, basta clicar no botão "Reverse" na linha da
transação desejada. Uma nova transação será criada na base de dados com o status    
"Reversed" tanto pra quem faz a reversão quanto para o quem recebeu a transação.
Como o depósito simula um saque, ele também será revertido, mas somente uma 
transação será criada.

# Testes 

Foi criado um teste unitário basico para o 'app/Services/WalletService', que é o
responsável pelo trabalho pesado da aplicação. Para executar os testes, basta 
executar o comando:

```bash
desafio-gac/src$ ./dockli php artisan test --env=testing
```


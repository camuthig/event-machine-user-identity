# event-machine-skeleton
Dockerized skeleton for prooph software [Event Machine](https://github.com/proophsoftware/event-machine)

## Installation
Please make sure you have installed [Docker](https://docs.docker.com/engine/installation/ "Install Docker") and [Docker Compose](https://docs.docker.com/compose/install/ "Install Docker Compose").

```bash
$ docker run --rm -it -v $(pwd):/app prooph/composer:7.1 create-project proophsoftware/event-machine-skeleton
$ cd event-machine-skeleton
$ sudo chown $(id -u -n):$(id -g -n) . -R
$ docker-compose up -d
$ docker-compose run php php scripts/create_event_stream.php
```
## Tutorial

[https://proophsoftware.github.io/event-machine/tutorial/](https://proophsoftware.github.io/event-machine/tutorial/)

## Demo

We've prepared a `demo` branch that contains a small service called `BuildingMgmt`. It will show you the basics of
event machine and the skeleton structure. To run the demo you have to clone the skeleton instead of
`composer create-project` so that your local copy is still connected to the github repo.

*Note: Event Machine is very flexible in the way how you organize your code. The skeleton just gives an example of a possible structure.
The default way is to use static aggregate methods as pure functions. However, it is also possible to use stateful OOP aggregates with a slightly 
different structure. If you prefer the OOP way you can checkout the "demo-oop" branch instead. Set up steps are identical. 
If you're unsure try both branches and see what coding style you prefer most.*

```bash
$ git clone https://github.com/proophsoftware/event-machine-skeleton.git prooph-building-mgmt
$ cd prooph-building-mgmt
$ git checkout demo
$ docker run --rm -it -v $(pwd):/app prooph/composer:7.1 install
$ docker-compose up -d
$ docker-compose run php php scripts/create_event_stream.php
```

Head over to `http://localhost:8080` to check if the containers are up and running.
You should see a "It works" message.

### Database

The skeleton uses a single Postgres database for both write and read model. The write model is event sourced and writes
all events to prooph/event-store. The read model is created by projections (see `src/Api/Projection`) and is also stored in
the Postgres DB. Read model tables have the prefix `em_ds_` and end with a version number which is by default `0_1_0`.

You can connect to the Postgres DB using following credentials (listed also in `app.env`):

```dotenv
PDO_DSN=pgsql:host=postgres port=5432 dbname=event_machine
PDO_USER=postgres
PDO_PWD=
```

### RabbitMQ

The skeleton uses RabbitMQ as a message broker with a preconfigured exchange called `ui-exchange` and a corresponding
queue called `ui-queue`. You can open the Rabbit Mgmt UI in the browser: `http://localhost:8081` and login with `user: prooph`
and `password: prooph`.

The skeleton also contains a demo JS client which connects to a websocket and consumes messages from the `ui-queue`.
Open `http://localhost:8080/ws.html` in your browser and forward events on the queue with `$eventMachine->on(Event::MY_EVENT, UiExchange::class)`.
Check `src/Api/Listener` for an example.

### GraphQL 
The skeleton exposes a GraphQL endpoint. You can send commands and queries using GraphQL.
Event machine automatically generates a GraphQL schema from your message and type schemas. 
For details check the comments in the various `src/Api` files.
 
The easiest way is to install one of the available GraphQL clients for Google Chrome:

- [ChromeiQL](https://chrome.google.com/webstore/detail/chromeiql/fkkiamalmpiidkljmicmjfbieiclmeij)
- [GraphiQL Feen](https://chrome.google.com/webstore/detail/graphiql-feen/mcbfdonlkfpbfdpimkjilhdneikhfklp)

Point your GraphQL client to `http://localhost:8080/api/graphql`. It should inspect the schema generated by event machine and suggest you
all available commands and queries.

### Try It

Try the demo by adding a building using a GraphQL client to send requests:

```graphql
mutation {
  AddBuilding(
    buildingId:"07e16c58-8ea7-43d5-b26c-afc002967074", 
    name:"prooph HQ"
  )
}

# Response:
#
# {
#   "data": {
#     "AddBuilding": true
#   }
# }
```

Next send a query to list all buildings (you can also filter by name and have options to skip and limit the result set):

```graphql
query {
  Buildings {
    buildingId
    name
    users   
  }
}

# Response:
#
# {
#    "data": {
#     "Buildings": [
#       {
#         "buildingId": "07e16c58-8ea7-43d5-b26c-afc002967074",
#         "name": "prooph HQ",
#         "users": []
#       }
#     ]
#   }
# }
```

Now that we have a building we can check-in a user by name.

```graphql
mutation {
  CheckInUser(
    buildingId:"07e16c58-8ea7-43d5-b26c-afc002967074", 
    name:"John"
  )
}

# Response:
#
# {
#   "data": {
#     "CheckInUser": true
#   }
# }
```

Send the previous query to list `Buildings` again. John should now be in the prooph HQ.

If everything worked so far you can take a look at the Postgres DB and se what we got on the persistence side.
We have an event stream with two events `BuildingMgmt.BuildingAdded` and `BuildingMgmt.UserCheckedIn`.
We also have a table called `em_ds_building_projection_0_1_0` that contains the added building with the checked in user John.

Try to check in John again and keep an eye on the JS websocket client that you can monitor in the browser: `http://localhost:8080/ws.html` 

Our small application detects **double check in** of a user and notifies security (us monitoring the `ui-queue`).

### Exercise

Implement the `Command::CHECK_OUT_USER`. Look at the classes in `src/Api`. They contain detailed comments and should guide you
to the process.

## Troubleshooting

With the command `docker-compose ps` you can list the running containers. This should look like the following list:

```bash
                    Name                                   Command               State                             Ports                           
---------------------------------------------------------------------------------------------------------------------------------------------------
proophbuildingmgmt_event_machine_projection_1   docker-php-entrypoint php  ...   Up                                                                
proophbuildingmgmt_nginx_1                      nginx -g daemon off;             Up      0.0.0.0:443->443/tcp, 0.0.0.0:8080->80/tcp                
proophbuildingmgmt_php_1                        docker-php-entrypoint php-fpm    Up      9000/tcp                                                  
proophbuildingmgmt_postgres_1                   docker-entrypoint.sh postgres    Up      0.0.0.0:5432->5432/tcp                                    
proophbuildingmgmt_rabbit_1                     docker-entrypoint.sh rabbi ...   Up      0.0.0.0:8081->15671/tcp, 15672/tcp,                       
                                                                                         0.0.0.0:15691->15691/tcp, 25672/tcp, 4369/tcp, 5671/tcp,  
                                                                                         5672/tcp 
```

Make sure that all required ports are available on your machine. If not you can modify port mapping in the `docker-compose.yml`.

### Have you tried turning it off and on again?

If something does not work as expected try to restart the containers first:

```bash
$ docker-compose down
$ docker-compose up -d
```

### Projection reset

Event machine uses a single projection process (read more about prooph projections in the [prooph docs](http://docs.getprooph.org/event-store/projections.html#3-4)).
You can register your own projections in event machine which are all handled by the one background process that is started automatically
with the script `bin/event_machine_projection.php`. Also see `docker-compose.yml`. The projection process runs in its own docker container
which is restarted by docker in case of a failure. The projection process dies from time to time to catch up with your latest code changes.

If you recognize that your read models are not up-to-date or you need to reset the read model you can use this command:

```bash
$ docker-compose run php php bin/reset.php
```

If you still have trouble try a step by step approach:

```bash
$ docker-compose stop event_machine_projection
$ docker-compose run php php bin/reset.php
$ docker-compose up -d
```

You can also check the projection log with:

```bash
$ docker-compose logs -f event_machine_projection
```

### GraphQL is not updated

When you add new commands or queries in event machine the GraphQL client will not automatically reread the schema from the backend.
Simply reload the GraphQL client or press `Set endpoint` button.


## Batteries Included

You know the headline from Docker, right?
The Event Machine skeleton follows the same principle. It ships with a default set up so that you can start without messing around with configuration and such.
The default set up is likely not what you want to use in production. The skeleton can be and **should be** adapted.

Focus of the skeleton is to provide *an easy to use development environment*, hence it uses default settings of Postgres and RabbitMQ containers.
**Make sure to secure the containers before you deploy them anywhere!** You should build and use your own docker containers in production anyway.
And if you cannot or don't want to use Docker then provide the needed infrastructure the way you prefer and just point event machine to it by adjusting configuration.

## Powered by prooph software

[![prooph software](https://github.com/codeliner/php-ddd-cargo-sample/blob/master/docs/assets/prooph-software-logo.png)](http://prooph.de)

Event Machine is maintained by the [prooph software team](http://prooph-software.de/). The source code of Event Machine 
is open sourced along with an API documentation and a getting started demo. Prooph software offers commercial support and workshops
for Event Machine as well as for the [prooph components](http://getprooph.org/).

If you are interested in this offer or need project support please [get in touch](http://getprooph.org/#get-in-touch).

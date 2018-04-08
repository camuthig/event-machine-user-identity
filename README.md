# Event Machine User Identity Skeleton

A dockerized skeleton for using prooph software's [Event Machine](https://github.com/proophsoftware/event-machine) RAD
with social authentication and CORS built in.

See the [documentation](https://proophsoftware.github.io/event-machine/intro/) for details on Event Machine.

The application currently supports Google login only, but can be expanded to other OAuth2 providers as well. A sample
consuming application has also be created [here](https://github.com/camuthig/event-machine-user-identity-app).

## Getting Started

Before getting started with this projection, copy `app.env.example` to `app.env` and add your client ID and secret for
the relevant OAuth2 providers.

For further directions on getting started, see the core skeleton's [README](https://github.com/proophsoftware/event-machine-skeleton).
The remainder of this documentation will focus on the features found in this alternative skeleton.

## Logging In

There is a single route, `POST /login/social`, for logging in. This route accepts two payload attributes, `provider` and
`token`. The only supported provider out of the box is `google`. The `token` attribute must be a valid OAuth2 access
token. The response will be a new JWT that consumers can use to send requests to the API.

## Authenticating Requests

API request authentication can be done in a number of ways. The manner currently implemented in this skeleton is using
[JWTs](https:://jtw.io). All requests to the `/api` routes (GraphQL server, messagebox, etc) require a valid JWT token
be supplied in the `Authorization` header. 

The JWTs have a 1 hour expiration, after that point, the application consuming the API will need to hit the 
`POST /login/social` route again to get a new token.

---
title: API Reference

language_tabs:
- php
- bash
- javascript

includes:

search: true

toc_footers:
- <a href='http://github.com/mpociot/documentarian'>Documentation Powered by Documentarian</a>
---
<!-- START_INFO -->
# Info

Welcome to the generated API reference.
[Get Postman Collection](http://monicalocal.test/docs/collection.json)

<!-- END_INFO -->

#Activities


<!-- START_7651fa39308e031728c794ef2c6be240 -->
## Get the list of activities.

<br><small style="padding: 1px 9px 2px;font-weight: bold;white-space: nowrap;color: #ffffff;-webkit-border-radius: 9px;-moz-border-radius: 9px;border-radius: 9px;background-color: #3a87ad;">Requires authentication</small>
> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/activities',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
        'query' => [
            'limit' => '3',
            'page' => '8',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activities?limit=3&page=8" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities"
);

let params = {
    "limit": "3",
    "page": "8",
};
Object.keys(params)
    .forEach(key => url.searchParams.append(key, params[key]));

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/activities`

#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
    `limit` |  optional  | int Indicates the page size
    `page` |  optional  | int Indicates the page to return

<!-- END_7651fa39308e031728c794ef2c6be240 -->

<!-- START_ae20a6beba7f4129ed09833ac1728b99 -->
## Store the activity.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/activities',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/activities" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/activities`


<!-- END_ae20a6beba7f4129ed09833ac1728b99 -->

<!-- START_c80b1e4f2293abbba46e63e089e9da48 -->
## Get the detail of a given activity.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/activities/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activities/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/activities/{activity}`


<!-- END_c80b1e4f2293abbba46e63e089e9da48 -->

<!-- START_12187850e57bf40c9bf553d5e33c3575 -->
## Update the activity.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/activities/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/activities/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/activities/{activity}`

`PATCH api/activities/{activity}`


<!-- END_12187850e57bf40c9bf553d5e33c3575 -->

<!-- START_e30c1ca5bbcb967bf61cf6c39f7acfa4 -->
## Delete an activity.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/activities/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/activities/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/activities/{activity}`


<!-- END_e30c1ca5bbcb967bf61cf6c39f7acfa4 -->

<!-- START_55ce9b405da7aece26d8e0d686b1b660 -->
## Get the list of activities for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/activities',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/activities" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/activities"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/activities`


<!-- END_55ce9b405da7aece26d8e0d686b1b660 -->

#Activity Type Categories


<!-- START_75269b2ab10144fc81137f3884fe1c18 -->
## Get the list of activity type categories.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/activitytypecategories',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activitytypecategories" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/activitytypecategories`


<!-- END_75269b2ab10144fc81137f3884fe1c18 -->

<!-- START_c81ecd528ab92b471b75287391488c15 -->
## Store the activity type category.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/activitytypecategories',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/activitytypecategories" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/activitytypecategories`


<!-- END_c81ecd528ab92b471b75287391488c15 -->

<!-- START_56b9983d0d8420b1911e9344ae04e362 -->
## Get the detail of a given activity type category.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/activitytypecategories/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activitytypecategories/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/activitytypecategories/{activitytypecategory}`


<!-- END_56b9983d0d8420b1911e9344ae04e362 -->

<!-- START_f81458cc224f02f65e6d64e36c01a0c9 -->
## Update the activity type category.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/activitytypecategories/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/activitytypecategories/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/activitytypecategories/{activitytypecategory}`

`PATCH api/activitytypecategories/{activitytypecategory}`


<!-- END_f81458cc224f02f65e6d64e36c01a0c9 -->

<!-- START_60cf5a34525da8974f09fa082645c8fe -->
## Delete an activity type category.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/activitytypecategories/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/activitytypecategories/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/activitytypecategories/{activitytypecategory}`


<!-- END_60cf5a34525da8974f09fa082645c8fe -->

#Activity Types


<!-- START_50b58c22e6424f9f6e44c35e980cbfca -->
## Get the list of activity types.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/activitytypes',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activitytypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/activitytypes`


<!-- END_50b58c22e6424f9f6e44c35e980cbfca -->

<!-- START_c6fbb838fcd22e7e52fb35a33b89d3b8 -->
## Store the activity type.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/activitytypes',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/activitytypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/activitytypes`


<!-- END_c6fbb838fcd22e7e52fb35a33b89d3b8 -->

<!-- START_f8904387316b8807b00c41f552029e76 -->
## Get the detail of a given activity type.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/activitytypes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activitytypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/activitytypes/{activitytype}`


<!-- END_f8904387316b8807b00c41f552029e76 -->

<!-- START_a9d6e966358fbe66b79e92f03046ffc8 -->
## Update the activity type.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/activitytypes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/activitytypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/activitytypes/{activitytype}`

`PATCH api/activitytypes/{activitytype}`


<!-- END_a9d6e966358fbe66b79e92f03046ffc8 -->

<!-- START_d8b91974a006067960047129a36ce210 -->
## Delete an activity type.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/activitytypes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/activitytypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/activitytypes/{activitytype}`


<!-- END_d8b91974a006067960047129a36ce210 -->

#Addresses


<!-- START_f62d434079dff3acd53aa774d160d2ad -->
## Get the list of addresses.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/addresses',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/addresses" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/addresses`


<!-- END_f62d434079dff3acd53aa774d160d2ad -->

<!-- START_c8fad65a796e6206c26cb584c46221b7 -->
## Store the address.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/addresses',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/addresses" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/addresses`


<!-- END_c8fad65a796e6206c26cb584c46221b7 -->

<!-- START_25f4303d28e06d127578df97937cdb67 -->
## Get the detail of a given address.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/addresses/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/addresses/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/addresses/{address}`


<!-- END_25f4303d28e06d127578df97937cdb67 -->

<!-- START_8f97ba08be391bb75680a4b5a24c9f6d -->
## Update the address.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/addresses/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/addresses/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/addresses/{address}`

`PATCH api/addresses/{address}`


<!-- END_8f97ba08be391bb75680a4b5a24c9f6d -->

<!-- START_e5d3d7a19170fe1ef6901a6ddf8eaeae -->
## Delete an address.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/addresses/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/addresses/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/addresses/{address}`


<!-- END_e5d3d7a19170fe1ef6901a6ddf8eaeae -->

<!-- START_a05270d95663f554c04f3578a3bd434c -->
## Get the list of addresses for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/addresses',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/addresses" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/addresses"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/addresses`


<!-- END_a05270d95663f554c04f3578a3bd434c -->

#Calls


<!-- START_f39b2e3781ab5d7ea23125345c5be75e -->
## Get the list of calls.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/calls',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/calls`


<!-- END_f39b2e3781ab5d7ea23125345c5be75e -->

<!-- START_4ab385e6babe171ebd61d88e554311bf -->
## Store the call.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/calls',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/calls`


<!-- END_4ab385e6babe171ebd61d88e554311bf -->

<!-- START_69b2082c217cd5a4fd92ca1b57da1c5a -->
## Get the detail of a given call.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/calls/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/calls/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/calls/{call}`


<!-- END_69b2082c217cd5a4fd92ca1b57da1c5a -->

<!-- START_2f310581427b19466445b4ae46eaa8f2 -->
## Update a call.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/calls/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/calls/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/calls/{call}`

`PATCH api/calls/{call}`


<!-- END_2f310581427b19466445b4ae46eaa8f2 -->

<!-- START_df9c23cad6c7fda24704de154816d0e7 -->
## Delete a call.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/calls/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/calls/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/calls/{call}`


<!-- END_df9c23cad6c7fda24704de154816d0e7 -->

<!-- START_0e92dbbb377956fb558bc888fb11527d -->
## Get the list of calls for a given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/calls',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/calls`


<!-- END_0e92dbbb377956fb558bc888fb11527d -->

#Companies


<!-- START_83764a2de1a941a0a3cbae52bba9776e -->
## Get the list of companies.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/companies',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/companies" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/companies`


<!-- END_83764a2de1a941a0a3cbae52bba9776e -->

<!-- START_a242a34f0abd359a9196226970606774 -->
## Store the company.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/companies',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/companies" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/companies`


<!-- END_a242a34f0abd359a9196226970606774 -->

<!-- START_b4015228dd0e0c0b6a959ebaf0865a05 -->
## Get the detail of a given company.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/companies/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/companies/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/companies/{company}`


<!-- END_b4015228dd0e0c0b6a959ebaf0865a05 -->

<!-- START_1e6a34851b0689db52677b43727419b5 -->
## Update a company.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/companies/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/companies/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/companies/{company}`

`PATCH api/companies/{company}`


<!-- END_1e6a34851b0689db52677b43727419b5 -->

<!-- START_72de66eabebc78e1d0e514081409da3a -->
## Delete a company.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/companies/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/companies/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/companies/{company}`


<!-- END_72de66eabebc78e1d0e514081409da3a -->

#Contact Field Types


<!-- START_20e1ef2faa9e28220eeed8eb18242993 -->
## Get the list of contact field types.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contactfieldtypes',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contactfieldtypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contactfieldtypes`


<!-- END_20e1ef2faa9e28220eeed8eb18242993 -->

<!-- START_6bafa4d9a8d87581a2f31388258d66f8 -->
## Store the contactfieldtype.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/contactfieldtypes',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/contactfieldtypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/contactfieldtypes`


<!-- END_6bafa4d9a8d87581a2f31388258d66f8 -->

<!-- START_f315c3eb9b55add217ddcc5333e1d666 -->
## Get the detail of a given contact field type.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contactfieldtypes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contactfieldtypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contactfieldtypes/{contactfieldtype}`


<!-- END_f315c3eb9b55add217ddcc5333e1d666 -->

<!-- START_ce34dc76ea233436e1958b2b806b6946 -->
## Update the contact field type.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/contactfieldtypes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/contactfieldtypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/contactfieldtypes/{contactfieldtype}`

`PATCH api/contactfieldtypes/{contactfieldtype}`


<!-- END_ce34dc76ea233436e1958b2b806b6946 -->

<!-- START_368c62f72301d410c78e42da011b8ad2 -->
## Delete an contactfieldtype.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/contactfieldtypes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/contactfieldtypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/contactfieldtypes/{contactfieldtype}`


<!-- END_368c62f72301d410c78e42da011b8ad2 -->

#Contact Fields


<!-- START_a89a3aa06b954aee5a63d98ce18f7992 -->
## Store the contactField.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/contactfields',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/contactfields" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfields"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/contactfields`


<!-- END_a89a3aa06b954aee5a63d98ce18f7992 -->

<!-- START_4ab8200795745727f1e7e1f6ceb9a087 -->
## Get the detail of a given contactField.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contactfields/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contactfields/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfields/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contactfields/{contactfield}`


<!-- END_4ab8200795745727f1e7e1f6ceb9a087 -->

<!-- START_64c738c591a84277b1cb115f5fa35246 -->
## Update the contactField.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/contactfields/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/contactfields/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfields/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/contactfields/{contactfield}`

`PATCH api/contactfields/{contactfield}`


<!-- END_64c738c591a84277b1cb115f5fa35246 -->

<!-- START_6fe422181f5193d56879a128ba7761a4 -->
## Delete a contactField.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/contactfields/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/contactfields/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfields/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/contactfields/{contactfield}`


<!-- END_6fe422181f5193d56879a128ba7761a4 -->

<!-- START_845060ef8acc2a61ca142ca2d1c68c12 -->
## Get the list of contact fields for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/contactfields',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/contactfields" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/contactfields"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/contactfields`


<!-- END_845060ef8acc2a61ca142ca2d1c68c12 -->

#Contacts


<!-- START_543b0b80e8dc51d2d3ad7e2a327eed26 -->
## Get the list of the contacts.

We will only retrieve the contacts that are "real", not the partials
ones.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts`


<!-- END_543b0b80e8dc51d2d3ad7e2a327eed26 -->

<!-- START_e1625404aaf762aa591c10b259222b07 -->
## Store the contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/contacts',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/contacts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/contacts`


<!-- END_e1625404aaf762aa591c10b259222b07 -->

<!-- START_a44483465b9aa8cdb47a73e922b4dd91 -->
## Get the detail of a given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}`


<!-- END_a44483465b9aa8cdb47a73e922b4dd91 -->

<!-- START_6855fa612757e2be32b2250d88260a29 -->
## Update the contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/contacts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/contacts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/contacts/{contact}`

`PATCH api/contacts/{contact}`


<!-- END_6855fa612757e2be32b2250d88260a29 -->

<!-- START_1143a8051a00b1611603a8cda0683f09 -->
## Delete a contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/contacts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/contacts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/contacts/{contact}`


<!-- END_1143a8051a00b1611603a8cda0683f09 -->

<!-- START_50f7c372942f6ef2df9dfc13e0f7a671 -->
## Set a contact as &#039;me&#039;.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/contacts/1/setMe',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/contacts/1/setMe" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/setMe"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/contacts/{contact}/setMe`


<!-- END_50f7c372942f6ef2df9dfc13e0f7a671 -->

#Conversations


<!-- START_50f5969ffa4376ab4d09a74616534468 -->
## Get the list of conversations.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/conversations',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/conversations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/conversations`


<!-- END_50f5969ffa4376ab4d09a74616534468 -->

<!-- START_1d6ac3c69bc5f2271b33806815418dc6 -->
## Store the conversation.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/conversations',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/conversations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/conversations`


<!-- END_1d6ac3c69bc5f2271b33806815418dc6 -->

<!-- START_91ffe2990f59ed18f147da555d60af64 -->
## Get the detail of a given conversation.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/conversations/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/conversations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/conversations/{conversation}`


<!-- END_91ffe2990f59ed18f147da555d60af64 -->

<!-- START_585f955d45f3290fce1897f0936ddb38 -->
## Update the conversation.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/conversations/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/conversations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/conversations/{conversation}`

`PATCH api/conversations/{conversation}`


<!-- END_585f955d45f3290fce1897f0936ddb38 -->

<!-- START_5d11e4c3e291ceae4cd41b2bff0d2e45 -->
## Destroy the conversation.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/conversations/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/conversations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/conversations/{conversation}`


<!-- END_5d11e4c3e291ceae4cd41b2bff0d2e45 -->

<!-- START_ba9d8dc7c576af515496ca031398c887 -->
## Get the list of conversations for a specific contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/conversations',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/conversations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/conversations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/conversations`


<!-- END_ba9d8dc7c576af515496ca031398c887 -->

#Debts


<!-- START_bb01ae3621e7fa60ffe6550de5f73945 -->
## Get the list of debts.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/debts',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/debts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/debts`


<!-- END_bb01ae3621e7fa60ffe6550de5f73945 -->

<!-- START_2f59f5ecb2f53bce8af0abf3f52908dc -->
## Store the debt.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/debts',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/debts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/debts`


<!-- END_2f59f5ecb2f53bce8af0abf3f52908dc -->

<!-- START_770e01d3360bdd2ada85b87bcc0cd9a0 -->
## Get the detail of a given debt.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/debts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/debts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/debts/{debt}`


<!-- END_770e01d3360bdd2ada85b87bcc0cd9a0 -->

<!-- START_c7b568dee9739fb9a8c07be209586334 -->
## Update the debt.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/debts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/debts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/debts/{debt}`

`PATCH api/debts/{debt}`


<!-- END_c7b568dee9739fb9a8c07be209586334 -->

<!-- START_af857305ca0fb670b744083371669a9c -->
## Delete a debt.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/debts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/debts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/debts/{debt}`


<!-- END_af857305ca0fb670b744083371669a9c -->

<!-- START_88bd819baf1ec9da815e4738dbfd4df4 -->
## Get the list of debts for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/debts',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/debts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/debts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/debts`


<!-- END_88bd819baf1ec9da815e4738dbfd4df4 -->

#Documents


<!-- START_2db491b98c54e01029e35dcce36f2edf -->
## Get the list of documents.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/documents',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/documents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/documents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/documents`


<!-- END_2db491b98c54e01029e35dcce36f2edf -->

<!-- START_73cdbcb5aa2de3c17d903aceef407d78 -->
## Get the detail of a given document.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/documents/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/documents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/documents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/documents/{document}`


<!-- END_73cdbcb5aa2de3c17d903aceef407d78 -->

<!-- START_fd15fa6846d168278d7085f22a2cd1e3 -->
## Get the list of documents for a specific contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/documents',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/documents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/documents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/documents`


<!-- END_fd15fa6846d168278d7085f22a2cd1e3 -->

#Genders


<!-- START_9c8ad14de9a6ed2b154a3e2a836ff686 -->
## Get the list of genders.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/genders',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/genders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/genders`


<!-- END_9c8ad14de9a6ed2b154a3e2a836ff686 -->

<!-- START_c1627d540a1e4e851a229d8af7239265 -->
## Store the gender.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/genders',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/genders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/genders`


<!-- END_c1627d540a1e4e851a229d8af7239265 -->

<!-- START_68717289f71f412d9dc1badb0cf9e322 -->
## Get the detail of a given gender.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/genders/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/genders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/genders/{gender}`


<!-- END_68717289f71f412d9dc1badb0cf9e322 -->

<!-- START_78403840ae21164785a057e9a44e7e20 -->
## Update a gender.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/genders/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/genders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/genders/{gender}`

`PATCH api/genders/{gender}`


<!-- END_78403840ae21164785a057e9a44e7e20 -->

<!-- START_2a67f14e27be54f99e734aaff492456d -->
## Delete a gender.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/genders/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/genders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/genders/{gender}`


<!-- END_2a67f14e27be54f99e734aaff492456d -->

#Gifts


<!-- START_965e17f1877da4447d9227c61e48b1d1 -->
## Get the list of gifts.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/gifts',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/gifts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/gifts`


<!-- END_965e17f1877da4447d9227c61e48b1d1 -->

<!-- START_72230615152e8879fdc448e920d68de5 -->
## Store the gift.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/gifts',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/gifts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/gifts`


<!-- END_72230615152e8879fdc448e920d68de5 -->

<!-- START_5edda6afb6fbe6807a22105f5d9792c6 -->
## Get the detail of a given gift.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/gifts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/gifts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/gifts/{gift}`


<!-- END_5edda6afb6fbe6807a22105f5d9792c6 -->

<!-- START_d772ddb617aeb9a0c32078529acd3da0 -->
## Update the gift.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/gifts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/gifts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/gifts/{gift}`

`PATCH api/gifts/{gift}`


<!-- END_d772ddb617aeb9a0c32078529acd3da0 -->

<!-- START_af571c04ab022e7ac6fab524fcb74b15 -->
## Delete a gift.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/gifts/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/gifts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/gifts/{gift}`


<!-- END_af571c04ab022e7ac6fab524fcb74b15 -->

<!-- START_421faf5d663ea12b808c4d2cc0b5afd0 -->
## Get the list of gifts for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/gifts',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/gifts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/gifts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/gifts`


<!-- END_421faf5d663ea12b808c4d2cc0b5afd0 -->

#Journals


<!-- START_0d9b4d8c77f9fa7803a08d3a82f5bc76 -->
## Get the list of journal entries.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/journal',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/journal" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/journal`


<!-- END_0d9b4d8c77f9fa7803a08d3a82f5bc76 -->

<!-- START_913adbebc140a76c892c0f9483949e11 -->
## Store the call.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/journal',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/journal" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/journal`


<!-- END_913adbebc140a76c892c0f9483949e11 -->

<!-- START_227f3459c6c02a4eaa16d1b569b5b877 -->
## Get the detail of a given journal entry.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/journal/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/journal/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/journal/{journal}`


<!-- END_227f3459c6c02a4eaa16d1b569b5b877 -->

<!-- START_0c3350ec076a9df3b7a6ec5c80660437 -->
## Update the note.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/journal/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/journal/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/journal/{journal}`

`PATCH api/journal/{journal}`


<!-- END_0c3350ec076a9df3b7a6ec5c80660437 -->

<!-- START_2a3d50e66055b5d9c837f5c3f37df048 -->
## Delete a journal entry.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/journal/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/journal/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/journal/{journal}`


<!-- END_2a3d50e66055b5d9c837f5c3f37df048 -->

#Life Events


<!-- START_25bbc444b878d0893b40f9f237062882 -->
## Get the list of life events.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/lifeevents',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/lifeevents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/lifeevents`


<!-- END_25bbc444b878d0893b40f9f237062882 -->

<!-- START_b0dcd0f39cd034964f0c43f5790d24dd -->
## Store the life event.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/lifeevents',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/lifeevents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/lifeevents`


<!-- END_b0dcd0f39cd034964f0c43f5790d24dd -->

<!-- START_a917fecbccdb3d7c45379345f36f33c7 -->
## Get the detail of a given life event.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/lifeevents/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/lifeevents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/lifeevents/{lifeevent}`


<!-- END_a917fecbccdb3d7c45379345f36f33c7 -->

<!-- START_66abc6c555a8b77d16241c511daa557a -->
## Update the life event.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/lifeevents/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/lifeevents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/lifeevents/{lifeevent}`

`PATCH api/lifeevents/{lifeevent}`


<!-- END_66abc6c555a8b77d16241c511daa557a -->

<!-- START_c2b86ab3c10a13c7cab82c590aa36f46 -->
## Destroy the life event.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/lifeevents/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/lifeevents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/lifeevents/{lifeevent}`


<!-- END_c2b86ab3c10a13c7cab82c590aa36f46 -->

#Messages


<!-- START_85aaf03897c57c83f214cfb0d3f57117 -->
## Store the message.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/conversations/1/messages',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/conversations/1/messages" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1/messages"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/conversations/{conversation}/messages`


<!-- END_85aaf03897c57c83f214cfb0d3f57117 -->

<!-- START_6ebd404556937c16489f98dfe1391176 -->
## Update the message.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/conversations/1/messages/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/conversations/1/messages/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1/messages/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/conversations/{conversation}/messages/{message}`

`PATCH api/conversations/{conversation}/messages/{message}`


<!-- END_6ebd404556937c16489f98dfe1391176 -->

<!-- START_cc05928690c5c137d9d47f0fa2b9d3fe -->
## Destroy the message.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/conversations/1/messages/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/conversations/1/messages/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1/messages/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/conversations/{conversation}/messages/{message}`


<!-- END_cc05928690c5c137d9d47f0fa2b9d3fe -->

#Misc


<!-- START_8ff05e238f60fee26c8b9f40731fa11f -->
## Get the list of general, public statistics.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/statistics',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/statistics" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/statistics"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
[
    {
        "instance_creation_date": "2019-11-17T16:41:05Z",
        "number_of_contacts": 0,
        "number_of_users": 0,
        "number_of_activities": 0,
        "number_of_reminders": 0,
        "number_of_new_users_last_week": 0
    }
]
```

### HTTP Request
`GET api/statistics`


<!-- END_8ff05e238f60fee26c8b9f40731fa11f -->

<!-- START_28735cbc755b629b410303cf105d2a29 -->
## Get the list of terms and privacy policies.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/compliance',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/compliance" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/compliance"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "data": [
        {
            "id": 1,
            "object": "term",
            "term_version": "2",
            "term_content": "\nScope of service\nMonica supports the following browsers:\n\nInternet Explorer (11+)\nFirefox (50+)\nChrome (latest)\nSafari (latest)\nI do not guarantee that the site will work with other browsers, but it’s very likely that it will just work.\n\nRights\nYou don’t have to provide your real name when you register to an account. You do however need a valid email address if you want to upgrade your account to the paid version, or receive reminders by email.\n\nYou have the right to close your account at any time.\n\nYou have the right to export your data at any time, in the SQL format.\n\nYour data will not be intentionally shown to other users or shared with third parties.\n\nYour personal data will not be shared with anyone without your consent.\n\nYour data is backed up every hour.\n\nIf the site ceases operation, you will receive an opportunity to export all your data before the site dies.\n\nAny new features that affect privacy will be strictly opt-in.\n\nResponsibilities\nYou will not use the site to store illegal information or data under the Canadian law (or any law).\n\nYou have to be at least 18+ to create an account and use the site.\n\nYou must not abuse the site by knowingly posting malicious code that could harm you or the other users.\n\nYou must only use the site to do things that are widely accepted as morally good.\n\nYou may not make automated requests to the site.\n\nYou may not abuse the invitation system.\n\nYou are responsible for keeping your account secure.\n\nI reserve the right to close accounts that abuse the system (thousands of contacts with hundred of thousands of reminders for instance) or use it in an unreasonable manner.\n\nOther important legal stuff\nThough I want to provide a great service, there are certain things about the service I cannot promise. For example, the services and software are provided “as-is”, at your own risk, without express or implied warranty or condition of any kind. I also disclaim any warranties of merchantability, fitness for a particular purpose or non-infringement. Monica will have no responsibility for any harm to your computer system, loss or corruption of data, or other harm that results from your access to or use of the Services or Software.\n\nThese Terms can change at any time, but I’ll never be a dick about it. Running this site is a dream come true to me, and I hope I’ll be able to run it as long as I can.\n        ",
            "privacy_version": "2",
            "privacy_content": "\nMonica is an open source project. The hosted version has a premium plan that let us collect money so we can pay for the servers and additional servers, but the main goal is not to make money (otherwise we wouldn’t have opened source it).\n\nMonica comes in two flavors: you can either use our hosted version, or download it and run it yourself. In the latter case, we do not track anything at all. We don’t know that you’ve even downloaded the product. Do whatever you want with it (but respect your local laws).\n\nWhen you create your account on our hosted version, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information.\n\nWhen you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.\n\nMonica runs on Linode and we are the only ones, apart from Linode’s employees, who have access to those servers.\n\nWe do hourly backups of the database.\n\nYour password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryptions mechanism, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.\n\nIf a data breach happens, we will contact the users who are affected to warn them about the breach.\n\nTransactional emails are dserved through Postmark.\n\nWe use an open source tool called Sentry to track errors that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets me debug what’s going on.\n\nThe site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.\n\nWe do no use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data, neither on the marketing site or the hosted version. We are deeply against their principles as they would use those data to profile you, which we are totally against.\n\nAll the data you put on Monica belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.\n\nAll the information about the contacts you put on Monica are private to you. We do not cross link information between accounts or use one information in an account to populate another account (unlike Facebook for instance).\n\nWe use Stripe to collect payments made to access the paid version. We do not store credit card information or anything concerning the transactions themselves on our servers. However, as per the open source library we use to process the payments (Laravel Cashier), we store the last 4 digits of the credit card, the brand name (VISA or MasterCard). As a user, you are identified on Stripe by a random number that they generate and use.\n\nRegarding the payments, you can downgrade to the free plan whenever you like. When you do, Stripe is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.\n\nYou can export your data at any time. You can also use the API to export all your data if you know how to do it. You can also request that we process this ourselves and send it to you. Your data will be exported in the SQL format.\n\nWhen you close your account, we immediately destroy all your personal information and don’t keep any backup. While you have control over this, we can delete an account for you if you ask us.\n\nIn certain situations, we may be required to disclose peronal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.\n\nIf you violate the terms of use we will terminate your account and notify you about it. However if you follow the \"don’t be a dick\" policy, nothing should ever happen to you and we’ll all be happy.\n\nMonica uses only open-source projects that are mainly hosted on Github.\n\nWe will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.",
            "created_at": "2018-04-12T00:00:00Z",
            "updated_at": null
        }
    ],
    "links": {
        "first": "http:\/\/localhost\/api\/compliance?page=1",
        "last": "http:\/\/localhost\/api\/compliance?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/compliance",
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

### HTTP Request
`GET api/compliance`


<!-- END_28735cbc755b629b410303cf105d2a29 -->

<!-- START_588591713af5f7498f10ece02cefd94a -->
## Get the detail of a given term.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/compliance/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/compliance/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/compliance/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "data": {
        "id": 1,
        "object": "term",
        "term_version": "2",
        "term_content": "\nScope of service\nMonica supports the following browsers:\n\nInternet Explorer (11+)\nFirefox (50+)\nChrome (latest)\nSafari (latest)\nI do not guarantee that the site will work with other browsers, but it’s very likely that it will just work.\n\nRights\nYou don’t have to provide your real name when you register to an account. You do however need a valid email address if you want to upgrade your account to the paid version, or receive reminders by email.\n\nYou have the right to close your account at any time.\n\nYou have the right to export your data at any time, in the SQL format.\n\nYour data will not be intentionally shown to other users or shared with third parties.\n\nYour personal data will not be shared with anyone without your consent.\n\nYour data is backed up every hour.\n\nIf the site ceases operation, you will receive an opportunity to export all your data before the site dies.\n\nAny new features that affect privacy will be strictly opt-in.\n\nResponsibilities\nYou will not use the site to store illegal information or data under the Canadian law (or any law).\n\nYou have to be at least 18+ to create an account and use the site.\n\nYou must not abuse the site by knowingly posting malicious code that could harm you or the other users.\n\nYou must only use the site to do things that are widely accepted as morally good.\n\nYou may not make automated requests to the site.\n\nYou may not abuse the invitation system.\n\nYou are responsible for keeping your account secure.\n\nI reserve the right to close accounts that abuse the system (thousands of contacts with hundred of thousands of reminders for instance) or use it in an unreasonable manner.\n\nOther important legal stuff\nThough I want to provide a great service, there are certain things about the service I cannot promise. For example, the services and software are provided “as-is”, at your own risk, without express or implied warranty or condition of any kind. I also disclaim any warranties of merchantability, fitness for a particular purpose or non-infringement. Monica will have no responsibility for any harm to your computer system, loss or corruption of data, or other harm that results from your access to or use of the Services or Software.\n\nThese Terms can change at any time, but I’ll never be a dick about it. Running this site is a dream come true to me, and I hope I’ll be able to run it as long as I can.\n        ",
        "privacy_version": "2",
        "privacy_content": "\nMonica is an open source project. The hosted version has a premium plan that let us collect money so we can pay for the servers and additional servers, but the main goal is not to make money (otherwise we wouldn’t have opened source it).\n\nMonica comes in two flavors: you can either use our hosted version, or download it and run it yourself. In the latter case, we do not track anything at all. We don’t know that you’ve even downloaded the product. Do whatever you want with it (but respect your local laws).\n\nWhen you create your account on our hosted version, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information.\n\nWhen you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.\n\nMonica runs on Linode and we are the only ones, apart from Linode’s employees, who have access to those servers.\n\nWe do hourly backups of the database.\n\nYour password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryptions mechanism, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.\n\nIf a data breach happens, we will contact the users who are affected to warn them about the breach.\n\nTransactional emails are dserved through Postmark.\n\nWe use an open source tool called Sentry to track errors that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets me debug what’s going on.\n\nThe site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.\n\nWe do no use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data, neither on the marketing site or the hosted version. We are deeply against their principles as they would use those data to profile you, which we are totally against.\n\nAll the data you put on Monica belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.\n\nAll the information about the contacts you put on Monica are private to you. We do not cross link information between accounts or use one information in an account to populate another account (unlike Facebook for instance).\n\nWe use Stripe to collect payments made to access the paid version. We do not store credit card information or anything concerning the transactions themselves on our servers. However, as per the open source library we use to process the payments (Laravel Cashier), we store the last 4 digits of the credit card, the brand name (VISA or MasterCard). As a user, you are identified on Stripe by a random number that they generate and use.\n\nRegarding the payments, you can downgrade to the free plan whenever you like. When you do, Stripe is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.\n\nYou can export your data at any time. You can also use the API to export all your data if you know how to do it. You can also request that we process this ourselves and send it to you. Your data will be exported in the SQL format.\n\nWhen you close your account, we immediately destroy all your personal information and don’t keep any backup. While you have control over this, we can delete an account for you if you ask us.\n\nIn certain situations, we may be required to disclose peronal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.\n\nIf you violate the terms of use we will terminate your account and notify you about it. However if you follow the \"don’t be a dick\" policy, nothing should ever happen to you and we’ll all be happy.\n\nMonica uses only open-source projects that are mainly hosted on Github.\n\nWe will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.",
        "created_at": "2018-04-12T00:00:00Z",
        "updated_at": null
    }
}
```

### HTTP Request
`GET api/compliance/{compliance}`


<!-- END_588591713af5f7498f10ece02cefd94a -->

<!-- START_aa2087c88a0544b7da514dfdd673acc0 -->
## Get the list of currencies.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/currencies',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/currencies" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/currencies"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "data": [
        {
            "id": 1,
            "object": "currency",
            "iso": "CAD",
            "name": "Canadian Dollar",
            "symbol": "$"
        },
        {
            "id": 2,
            "object": "currency",
            "iso": "USD",
            "name": "US Dollar",
            "symbol": "$"
        },
        {
            "id": 3,
            "object": "currency",
            "iso": "GBP",
            "name": "British Pound",
            "symbol": "£"
        },
        {
            "id": 4,
            "object": "currency",
            "iso": "EUR",
            "name": "Euro",
            "symbol": "€"
        },
        {
            "id": 5,
            "object": "currency",
            "iso": "RUB",
            "name": "Russian Ruble",
            "symbol": "₽"
        },
        {
            "id": 6,
            "object": "currency",
            "iso": "ZAR",
            "name": "South African Rand",
            "symbol": "R "
        },
        {
            "id": 7,
            "object": "currency",
            "iso": "DKK",
            "name": "Danish krone",
            "symbol": "kr."
        },
        {
            "id": 8,
            "object": "currency",
            "iso": "INR",
            "name": "Indian rupee",
            "symbol": "₹"
        },
        {
            "id": 9,
            "object": "currency",
            "iso": "BRL",
            "name": "Brazilian Real",
            "symbol": "R$"
        },
        {
            "id": 10,
            "object": "currency",
            "iso": "CHF",
            "name": "Swiss CHF",
            "symbol": "CHF"
        },
        {
            "id": 11,
            "object": "currency",
            "iso": "AED",
            "name": "Emirati Dirham",
            "symbol": ".د.ب"
        },
        {
            "id": 12,
            "object": "currency",
            "iso": "AFN",
            "name": "Afghan Afghani",
            "symbol": "؋"
        },
        {
            "id": 13,
            "object": "currency",
            "iso": "ALL",
            "name": "Albanian lek",
            "symbol": "lek"
        },
        {
            "id": 14,
            "object": "currency",
            "iso": "AMD",
            "name": "Armenian dram",
            "symbol": ""
        },
        {
            "id": 15,
            "object": "currency",
            "iso": "ANG",
            "name": "Dutch Guilder",
            "symbol": "ƒ"
        }
    ],
    "links": {
        "first": "http:\/\/localhost\/api\/currencies?page=1",
        "last": "http:\/\/localhost\/api\/currencies?page=11",
        "prev": null,
        "next": "http:\/\/localhost\/api\/currencies?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 11,
        "path": "http:\/\/localhost\/api\/currencies",
        "per_page": 15,
        "to": 15,
        "total": 153
    }
}
```

### HTTP Request
`GET api/currencies`


<!-- END_aa2087c88a0544b7da514dfdd673acc0 -->

<!-- START_dbc92b87f08648e5fc649f6677876ac0 -->
## Get the detail of a given currency.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/currencies/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/currencies/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/currencies/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
{
    "data": {
        "id": 1,
        "object": "currency",
        "iso": "CAD",
        "name": "Canadian Dollar",
        "symbol": "$"
    }
}
```

### HTTP Request
`GET api/currencies/{currency}`


<!-- END_dbc92b87f08648e5fc649f6677876ac0 -->

<!-- START_316a4c3b4f6a4c4ff34e5893943cdebd -->
## Get the list of countries.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/countries',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/countries" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/countries"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/countries`


<!-- END_316a4c3b4f6a4c4ff34e5893943cdebd -->

#Notes


<!-- START_5184c63b96049910fee7fc65756de436 -->
## Get the list of notes.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/notes',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/notes`


<!-- END_5184c63b96049910fee7fc65756de436 -->

<!-- START_fc4b6ae244ae158e33e19e0d56b80711 -->
## Store the note.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/notes',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/notes`


<!-- END_fc4b6ae244ae158e33e19e0d56b80711 -->

<!-- START_7c30ddc7968295f3665e9e7e4712506d -->
## Get the detail of a given note.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/notes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/notes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/notes/{note}`


<!-- END_7c30ddc7968295f3665e9e7e4712506d -->

<!-- START_e17ba28433f1ed23cb7a68ebbfcafa11 -->
## Update the note.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/notes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/notes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/notes/{note}`

`PATCH api/notes/{note}`


<!-- END_e17ba28433f1ed23cb7a68ebbfcafa11 -->

<!-- START_9541e0368a1f31ee173647e886e97f45 -->
## Delete a note.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/notes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/notes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/notes/{note}`


<!-- END_9541e0368a1f31ee173647e886e97f45 -->

<!-- START_fdd8df9779f2ebe397ed5e6a2ff7473b -->
## Get the list of notes for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/notes',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/notes`


<!-- END_fdd8df9779f2ebe397ed5e6a2ff7473b -->

#OAuth Authentication


<!-- START_9bb93d7ddf3f622bc99d0f644c88f537 -->
## Display a log in form for oauth accessToken.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/oauth/login',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/oauth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/login"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (200):

```json
null
```

### HTTP Request
`GET oauth/login`


<!-- END_9bb93d7ddf3f622bc99d0f644c88f537 -->

<!-- START_c599020b8dabac913a5a326a94d61a23 -->
## Log in a user and returns an accessToken.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/oauth/login',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/oauth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/login"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (422):

```json
{
    "error": {
        "message": [
            "The email field is required.",
            "The password field is required."
        ],
        "error_code": 32
    }
}
```

### HTTP Request
`POST oauth/login`


<!-- END_c599020b8dabac913a5a326a94d61a23 -->

#Occupations


<!-- START_44fc9d2f31e34bec296d01a4fd3b4f64 -->
## Get the list of occupations.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/occupations',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/occupations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/occupations`


<!-- END_44fc9d2f31e34bec296d01a4fd3b4f64 -->

<!-- START_94a278dfa6b18e34c60ccc41fdf72fbb -->
## Store the occupation.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/occupations',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/occupations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/occupations`


<!-- END_94a278dfa6b18e34c60ccc41fdf72fbb -->

<!-- START_1a5156301ff2f3cd6b0816ee2303331d -->
## Get the detail of a given occupation.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/occupations/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/occupations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/occupations/{occupation}`


<!-- END_1a5156301ff2f3cd6b0816ee2303331d -->

<!-- START_9b48b3be226fa34bfad64e60ea8b915f -->
## Update an occupation.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/occupations/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/occupations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/occupations/{occupation}`

`PATCH api/occupations/{occupation}`


<!-- END_9b48b3be226fa34bfad64e60ea8b915f -->

<!-- START_55ce54d26a88d070e333a42baabef95d -->
## Delete an occupation.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/occupations/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/occupations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/occupations/{occupation}`


<!-- END_55ce54d26a88d070e333a42baabef95d -->

#Pets


<!-- START_e0c1b7b4dea17c1d1ff6c3b57f7f4082 -->
## Get the list of pet.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/pets',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/pets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/pets`


<!-- END_e0c1b7b4dea17c1d1ff6c3b57f7f4082 -->

<!-- START_cbb5a5c7bc1cde4598c0a554fa1ed829 -->
## Store the pet.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/pets',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/pets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/pets`


<!-- END_cbb5a5c7bc1cde4598c0a554fa1ed829 -->

<!-- START_7f555f395aff7f195f4c5b5c11d33ccd -->
## Get the detail of a given pet.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/pets/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/pets/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/pets/{pet}`


<!-- END_7f555f395aff7f195f4c5b5c11d33ccd -->

<!-- START_d20e803533b81c7387387db2c9038e98 -->
## Update the pet.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/pets/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/pets/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/pets/{pet}`

`PATCH api/pets/{pet}`


<!-- END_d20e803533b81c7387387db2c9038e98 -->

<!-- START_f64f4b8a970dc8ac4a08d29fe696cc37 -->
## Delete a pet.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/pets/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/pets/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/pets/{pet}`


<!-- END_f64f4b8a970dc8ac4a08d29fe696cc37 -->

<!-- START_b61bb60b2c9acc1193b64a81630077d2 -->
## Get the list of pets for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/pets',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/pets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/pets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/pets`


<!-- END_b61bb60b2c9acc1193b64a81630077d2 -->

#Places


<!-- START_7e65c6380b56f3836f914d63f895bd42 -->
## Get the list of places.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/places',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/places" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/places`


<!-- END_7e65c6380b56f3836f914d63f895bd42 -->

<!-- START_4c3e06c510713e3a9090bbf1c008203a -->
## Store the place.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/places',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/places" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/places`


<!-- END_4c3e06c510713e3a9090bbf1c008203a -->

<!-- START_dd9635f21ef0081d95c38071781376f5 -->
## Get the detail of a given place.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/places/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/places/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/places/{place}`


<!-- END_dd9635f21ef0081d95c38071781376f5 -->

<!-- START_38859726f694afa752fe160e8c4920da -->
## Update a place.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/places/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/places/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/places/{place}`

`PATCH api/places/{place}`


<!-- END_38859726f694afa752fe160e8c4920da -->

<!-- START_90449332adbee73e240f8ef672543a3a -->
## Delete a place.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/places/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/places/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/places/{place}`


<!-- END_90449332adbee73e240f8ef672543a3a -->

#Relationship Type Groups


<!-- START_01117b30a8e4332606627a000667d176 -->
## Get all relationship type groups in an instance.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/relationshiptypegroups',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationshiptypegroups" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationshiptypegroups"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/relationshiptypegroups`


<!-- END_01117b30a8e4332606627a000667d176 -->

<!-- START_e211dc0774124cfa939bf1995373aec0 -->
## Get the detail of a given relationship type group.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/relationshiptypegroups/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationshiptypegroups/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationshiptypegroups/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/relationshiptypegroups/{relationshiptypegroup}`


<!-- END_e211dc0774124cfa939bf1995373aec0 -->

#Relationship Types


<!-- START_aff6c6f170abaa78630729b1a92a50da -->
## Get all relationship types in an instance.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/relationshiptypes',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationshiptypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationshiptypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/relationshiptypes`


<!-- END_aff6c6f170abaa78630729b1a92a50da -->

<!-- START_a1485371ba89d68ffdcf4d043f149286 -->
## Get the detail of a given relationship type.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/relationshiptypes/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationshiptypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationshiptypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/relationshiptypes/{relationshiptype}`


<!-- END_a1485371ba89d68ffdcf4d043f149286 -->

#Relationships


<!-- START_36a3b4ac5edc618fd2104f7029c7849a -->
## Create a new relationship.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/relationships',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/relationships" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationships"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/relationships`


<!-- END_36a3b4ac5edc618fd2104f7029c7849a -->

<!-- START_211bdcc77418eb92671e24e623788444 -->
## Get the detail of a given relationship.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/relationships/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationships/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationships/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/relationships/{relationship}`


<!-- END_211bdcc77418eb92671e24e623788444 -->

<!-- START_533074e3799d9ee6ec97d4a3d4f1b46b -->
## Update an existing relationship.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/relationships/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/relationships/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationships/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/relationships/{relationship}`

`PATCH api/relationships/{relationship}`


<!-- END_533074e3799d9ee6ec97d4a3d4f1b46b -->

<!-- START_0151793b11bff7a72c0b4c7eabf2288f -->
## Delete a relationship.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/relationships/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/relationships/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationships/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/relationships/{relationship}`


<!-- END_0151793b11bff7a72c0b4c7eabf2288f -->

<!-- START_033b0df90343be6d8201ca1ab91a16c1 -->
## Get all of relationships of a contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/relationships',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/relationships" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/relationships"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/relationships`


<!-- END_033b0df90343be6d8201ca1ab91a16c1 -->

#Reminders


<!-- START_fc8b7b4c9225175cc83fb11f750b3836 -->
## Get the list of reminders.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/reminders',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/reminders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/reminders`


<!-- END_fc8b7b4c9225175cc83fb11f750b3836 -->

<!-- START_0984ac5a08321d0fb88aaaa1789ec5eb -->
## Store the reminder.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/reminders',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/reminders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/reminders`


<!-- END_0984ac5a08321d0fb88aaaa1789ec5eb -->

<!-- START_5eb56fa8f5b62926fa180c5662a4eb1b -->
## Get the detail of a given reminder.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/reminders/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/reminders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/reminders/{reminder}`


<!-- END_5eb56fa8f5b62926fa180c5662a4eb1b -->

<!-- START_d50c1638768ef084cd00d5a9bde204c0 -->
## Update the reminder.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/reminders/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/reminders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/reminders/{reminder}`

`PATCH api/reminders/{reminder}`


<!-- END_d50c1638768ef084cd00d5a9bde204c0 -->

<!-- START_fd37f2a81bb0f911528f9b10f8759a59 -->
## Delete a reminder.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/reminders/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/reminders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/reminders/{reminder}`


<!-- END_fd37f2a81bb0f911528f9b10f8759a59 -->

<!-- START_085928bc6ada4c42f7e31e8cea93dda1 -->
## Get the list of reminders for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/reminders',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/reminders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/reminders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/reminders`


<!-- END_085928bc6ada4c42f7e31e8cea93dda1 -->

#Tags


<!-- START_d0ed144b117d0ed3111aab4cd1ffd25a -->
## Associate one or more tags to the contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/contacts/1/setTags',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/contacts/1/setTags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/setTags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/contacts/{contact}/setTags`


<!-- END_d0ed144b117d0ed3111aab4cd1ffd25a -->

<!-- START_128ee14ecacc3cf46357b198e753176a -->
## Remove all the tags associated with the contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/contacts/1/unsetTags',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/contacts/1/unsetTags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/unsetTags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/contacts/{contact}/unsetTags`


<!-- END_128ee14ecacc3cf46357b198e753176a -->

<!-- START_f362d4ee3f66ba2111a83c58682e246f -->
## Remove one or more specific tags associated with the contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/contacts/1/unsetTag',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/contacts/1/unsetTag" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/unsetTag"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/contacts/{contact}/unsetTag`


<!-- END_f362d4ee3f66ba2111a83c58682e246f -->

<!-- START_dde6989ab5551d4fb09439f7cb2554c5 -->
## Get the list of the contacts.

We will only retrieve the contacts that are "real", not the partials
ones.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/tags',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/tags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/tags`


<!-- END_dde6989ab5551d4fb09439f7cb2554c5 -->

<!-- START_6b95d7d1e0e5c34dd24d290bc715dccb -->
## Store the tag.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/tags',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/tags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/tags`


<!-- END_6b95d7d1e0e5c34dd24d290bc715dccb -->

<!-- START_faf1227d26e1a9f94cda4bb6a688c251 -->
## Get the detail of a given tag.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/tags/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/tags/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/tags/{tag}`


<!-- END_faf1227d26e1a9f94cda4bb6a688c251 -->

<!-- START_a4a8a57c60acc8638ef566d690878aca -->
## Update the tag.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/tags/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/tags/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/tags/{tag}`

`PATCH api/tags/{tag}`


<!-- END_a4a8a57c60acc8638ef566d690878aca -->

<!-- START_4a7e5df1eb6e21ac01a7ea9dbd9bf710 -->
## Delete a tag.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/tags/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/tags/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/tags/{tag}`


<!-- END_4a7e5df1eb6e21ac01a7ea9dbd9bf710 -->

#Tasks


<!-- START_4227b9e5e54912af051e8dd5472afbce -->
## Get the list of task.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/tasks',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/tasks`


<!-- END_4227b9e5e54912af051e8dd5472afbce -->

<!-- START_4da0d9b378428dcc89ced395d4a806e7 -->
## Store the task.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/tasks',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/tasks`


<!-- END_4da0d9b378428dcc89ced395d4a806e7 -->

<!-- START_5297efa151ae4fd515fec2efd5cb1e9a -->
## Get the detail of a given task.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/tasks/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/tasks/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/tasks/{task}`


<!-- END_5297efa151ae4fd515fec2efd5cb1e9a -->

<!-- START_546f027bf591f2ef4a8a743f0a59051d -->
## Update the task.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->put(
    'https://monicalocal.test/api/tasks/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X PUT \
    "https://monicalocal.test/api/tasks/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`PUT api/tasks/{task}`

`PATCH api/tasks/{task}`


<!-- END_546f027bf591f2ef4a8a743f0a59051d -->

<!-- START_8b8069956f22facfa8cdc67aece156a8 -->
## Delete a task.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->delete(
    'https://monicalocal.test/api/tasks/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X DELETE \
    "https://monicalocal.test/api/tasks/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`DELETE api/tasks/{task}`


<!-- END_8b8069956f22facfa8cdc67aece156a8 -->

<!-- START_c6eeee5f958cc1a8c98fb86041d52f06 -->
## Get the list of tasks for the given contact.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/contacts/1/tasks',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/contacts/{contact}/tasks`


<!-- END_c6eeee5f958cc1a8c98fb86041d52f06 -->

#Users


<!-- START_b19e2ecbb41b5fa6802edaf581aab5f6 -->
## Get the detail of the authenticated user.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/me',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/me" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/me"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/me`


<!-- END_b19e2ecbb41b5fa6802edaf581aab5f6 -->

<!-- START_c4fa2f9c78d988b16c201e9beae5059e -->
## Get all the policies ever signed by the authenticated user.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/me/compliance',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/me/compliance" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/me/compliance"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/me/compliance`


<!-- END_c4fa2f9c78d988b16c201e9beae5059e -->

<!-- START_5edb4677580f92db7359521b6afd2964 -->
## Get the state of a specific term for the user.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'https://monicalocal.test/api/me/compliance/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X GET \
    -G "https://monicalocal.test/api/me/compliance/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/me/compliance/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`GET api/me/compliance/{id}`


<!-- END_5edb4677580f92db7359521b6afd2964 -->

<!-- START_93263693603c69f0172b2474dd65074c -->
## Sign the latest policy for the authenticated user.

> Example request:

```php

$client = new \GuzzleHttp\Client();
$response = $client->post(
    'https://monicalocal.test/api/me/compliance',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer {token}',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```bash
curl -X POST \
    "https://monicalocal.test/api/me/compliance" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/me/compliance"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (401):

```json
{
    "message": "Unauthenticated."
}
```

### HTTP Request
`POST api/me/compliance`


<!-- END_93263693603c69f0172b2474dd65074c -->



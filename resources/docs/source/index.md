---
title: API Reference

language_tabs:
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activities?limit=11&page=18" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities"
);

let params = {
    "limit": "11",
    "page": "18",
};
Object.keys(params)
    .forEach(key => url.searchParams.append(key, params[key]));

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/activities" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/activities`


<!-- END_ae20a6beba7f4129ed09833ac1728b99 -->

<!-- START_c80b1e4f2293abbba46e63e089e9da48 -->
## Get the detail of a given activity.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activities/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/activities/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/activities/{activity}`

`PATCH api/activities/{activity}`


<!-- END_12187850e57bf40c9bf553d5e33c3575 -->

<!-- START_e30c1ca5bbcb967bf61cf6c39f7acfa4 -->
## Delete an activity.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/activities/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activities/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/activities/{activity}`


<!-- END_e30c1ca5bbcb967bf61cf6c39f7acfa4 -->

<!-- START_55ce9b405da7aece26d8e0d686b1b660 -->
## Get the list of activities for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/activities" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/activities"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activitytypecategories" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/activitytypecategories" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/activitytypecategories`


<!-- END_c81ecd528ab92b471b75287391488c15 -->

<!-- START_56b9983d0d8420b1911e9344ae04e362 -->
## Get the detail of a given activity type category.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activitytypecategories/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/activitytypecategories/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/activitytypecategories/{activitytypecategory}`

`PATCH api/activitytypecategories/{activitytypecategory}`


<!-- END_f81458cc224f02f65e6d64e36c01a0c9 -->

<!-- START_60cf5a34525da8974f09fa082645c8fe -->
## Delete an activity type category.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/activitytypecategories/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypecategories/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/activitytypecategories/{activitytypecategory}`


<!-- END_60cf5a34525da8974f09fa082645c8fe -->

#Activity Types


<!-- START_50b58c22e6424f9f6e44c35e980cbfca -->
## Get the list of activity types.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activitytypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/activitytypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/activitytypes`


<!-- END_c6fbb838fcd22e7e52fb35a33b89d3b8 -->

<!-- START_f8904387316b8807b00c41f552029e76 -->
## Get the detail of a given activity type.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/activitytypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/activitytypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/activitytypes/{activitytype}`

`PATCH api/activitytypes/{activitytype}`


<!-- END_a9d6e966358fbe66b79e92f03046ffc8 -->

<!-- START_d8b91974a006067960047129a36ce210 -->
## Delete an activity type.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/activitytypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/activitytypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/activitytypes/{activitytype}`


<!-- END_d8b91974a006067960047129a36ce210 -->

#Addresses


<!-- START_f62d434079dff3acd53aa774d160d2ad -->
## Get the list of addresses.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/addresses" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/addresses" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/addresses`


<!-- END_c8fad65a796e6206c26cb584c46221b7 -->

<!-- START_25f4303d28e06d127578df97937cdb67 -->
## Get the detail of a given address.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/addresses/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/addresses/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/addresses/{address}`

`PATCH api/addresses/{address}`


<!-- END_8f97ba08be391bb75680a4b5a24c9f6d -->

<!-- START_e5d3d7a19170fe1ef6901a6ddf8eaeae -->
## Delete an address.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/addresses/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/addresses/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/addresses/{address}`


<!-- END_e5d3d7a19170fe1ef6901a6ddf8eaeae -->

<!-- START_a05270d95663f554c04f3578a3bd434c -->
## Get the list of addresses for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/addresses" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/addresses"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/calls`


<!-- END_4ab385e6babe171ebd61d88e554311bf -->

<!-- START_69b2082c217cd5a4fd92ca1b57da1c5a -->
## Get the detail of a given call.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/calls/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/calls/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/calls/{call}`

`PATCH api/calls/{call}`


<!-- END_2f310581427b19466445b4ae46eaa8f2 -->

<!-- START_df9c23cad6c7fda24704de154816d0e7 -->
## Delete a call.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/calls/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/calls/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/calls/{call}`


<!-- END_df9c23cad6c7fda24704de154816d0e7 -->

<!-- START_0e92dbbb377956fb558bc888fb11527d -->
## Get the list of calls for a given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/companies" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/companies" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/companies`


<!-- END_a242a34f0abd359a9196226970606774 -->

<!-- START_b4015228dd0e0c0b6a959ebaf0865a05 -->
## Get the detail of a given company.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/companies/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/companies/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/companies/{company}`

`PATCH api/companies/{company}`


<!-- END_1e6a34851b0689db52677b43727419b5 -->

<!-- START_72de66eabebc78e1d0e514081409da3a -->
## Delete a company.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/companies/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/companies/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/companies/{company}`


<!-- END_72de66eabebc78e1d0e514081409da3a -->

#Contact Field Types


<!-- START_20e1ef2faa9e28220eeed8eb18242993 -->
## Get the list of contact field types.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contactfieldtypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/contactfieldtypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/contactfieldtypes`


<!-- END_6bafa4d9a8d87581a2f31388258d66f8 -->

<!-- START_f315c3eb9b55add217ddcc5333e1d666 -->
## Get the detail of a given contact field type.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contactfieldtypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/contactfieldtypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/contactfieldtypes/{contactfieldtype}`

`PATCH api/contactfieldtypes/{contactfieldtype}`


<!-- END_ce34dc76ea233436e1958b2b806b6946 -->

<!-- START_368c62f72301d410c78e42da011b8ad2 -->
## Delete an contactfieldtype.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/contactfieldtypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfieldtypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/contactfieldtypes/{contactfieldtype}`


<!-- END_368c62f72301d410c78e42da011b8ad2 -->

#Contact Fields


<!-- START_a89a3aa06b954aee5a63d98ce18f7992 -->
## Store the contactField.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/api/contactfields" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfields"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/contactfields`


<!-- END_a89a3aa06b954aee5a63d98ce18f7992 -->

<!-- START_4ab8200795745727f1e7e1f6ceb9a087 -->
## Get the detail of a given contactField.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contactfields/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfields/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/contactfields/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfields/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/contactfields/{contactfield}`

`PATCH api/contactfields/{contactfield}`


<!-- END_64c738c591a84277b1cb115f5fa35246 -->

<!-- START_6fe422181f5193d56879a128ba7761a4 -->
## Delete a contactField.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/contactfields/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contactfields/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/contactfields/{contactfield}`


<!-- END_6fe422181f5193d56879a128ba7761a4 -->

<!-- START_845060ef8acc2a61ca142ca2d1c68c12 -->
## Get the list of contact fields for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/contactfields" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/contactfields"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/contacts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/contacts`


<!-- END_e1625404aaf762aa591c10b259222b07 -->

<!-- START_a44483465b9aa8cdb47a73e922b4dd91 -->
## Get the detail of a given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/contacts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/contacts/{contact}`

`PATCH api/contacts/{contact}`


<!-- END_6855fa612757e2be32b2250d88260a29 -->

<!-- START_1143a8051a00b1611603a8cda0683f09 -->
## Delete a contact.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/contacts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/contacts/{contact}`


<!-- END_1143a8051a00b1611603a8cda0683f09 -->

<!-- START_50f7c372942f6ef2df9dfc13e0f7a671 -->
## Set a contact as &#039;me&#039;.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/api/contacts/1/setMe" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/setMe"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/contacts/{contact}/setMe`


<!-- END_50f7c372942f6ef2df9dfc13e0f7a671 -->

#Conversations


<!-- START_50f5969ffa4376ab4d09a74616534468 -->
## Get the list of conversations.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/conversations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/conversations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/conversations`


<!-- END_1d6ac3c69bc5f2271b33806815418dc6 -->

<!-- START_91ffe2990f59ed18f147da555d60af64 -->
## Get the detail of a given conversation.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/conversations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/conversations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/conversations/{conversation}`

`PATCH api/conversations/{conversation}`


<!-- END_585f955d45f3290fce1897f0936ddb38 -->

<!-- START_5d11e4c3e291ceae4cd41b2bff0d2e45 -->
## Destroy the conversation.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/conversations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/conversations/{conversation}`


<!-- END_5d11e4c3e291ceae4cd41b2bff0d2e45 -->

<!-- START_ba9d8dc7c576af515496ca031398c887 -->
## Get the list of conversations for a specific contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/conversations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/conversations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/debts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/debts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/debts`


<!-- END_2f59f5ecb2f53bce8af0abf3f52908dc -->

<!-- START_770e01d3360bdd2ada85b87bcc0cd9a0 -->
## Get the detail of a given debt.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/debts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/debts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/debts/{debt}`

`PATCH api/debts/{debt}`


<!-- END_c7b568dee9739fb9a8c07be209586334 -->

<!-- START_af857305ca0fb670b744083371669a9c -->
## Delete a debt.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/debts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/debts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/debts/{debt}`


<!-- END_af857305ca0fb670b744083371669a9c -->

<!-- START_88bd819baf1ec9da815e4738dbfd4df4 -->
## Get the list of debts for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/debts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/debts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/documents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/documents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/documents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/documents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/documents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/documents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/genders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/genders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/genders`


<!-- END_c1627d540a1e4e851a229d8af7239265 -->

<!-- START_68717289f71f412d9dc1badb0cf9e322 -->
## Get the detail of a given gender.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/genders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/genders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/genders/{gender}`

`PATCH api/genders/{gender}`


<!-- END_78403840ae21164785a057e9a44e7e20 -->

<!-- START_2a67f14e27be54f99e734aaff492456d -->
## Delete a gender.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/genders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/genders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/genders/{gender}`


<!-- END_2a67f14e27be54f99e734aaff492456d -->

#Gifts


<!-- START_965e17f1877da4447d9227c61e48b1d1 -->
## Get the list of gifts.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/gifts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/gifts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/gifts`


<!-- END_72230615152e8879fdc448e920d68de5 -->

<!-- START_5edda6afb6fbe6807a22105f5d9792c6 -->
## Get the detail of a given gift.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/gifts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/gifts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/gifts/{gift}`

`PATCH api/gifts/{gift}`


<!-- END_d772ddb617aeb9a0c32078529acd3da0 -->

<!-- START_af571c04ab022e7ac6fab524fcb74b15 -->
## Delete a gift.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/gifts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/gifts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/gifts/{gift}`


<!-- END_af571c04ab022e7ac6fab524fcb74b15 -->

<!-- START_421faf5d663ea12b808c4d2cc0b5afd0 -->
## Get the list of gifts for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/gifts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/gifts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/journal" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/journal" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/journal`


<!-- END_913adbebc140a76c892c0f9483949e11 -->

<!-- START_227f3459c6c02a4eaa16d1b569b5b877 -->
## Get the detail of a given journal entry.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/journal/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/journal/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/journal/{journal}`

`PATCH api/journal/{journal}`


<!-- END_0c3350ec076a9df3b7a6ec5c80660437 -->

<!-- START_2a3d50e66055b5d9c837f5c3f37df048 -->
## Delete a journal entry.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/journal/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/journal/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/journal/{journal}`


<!-- END_2a3d50e66055b5d9c837f5c3f37df048 -->

#Life Events


<!-- START_25bbc444b878d0893b40f9f237062882 -->
## Get the list of life events.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/lifeevents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/lifeevents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/lifeevents`


<!-- END_b0dcd0f39cd034964f0c43f5790d24dd -->

<!-- START_a917fecbccdb3d7c45379345f36f33c7 -->
## Get the detail of a given life event.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/lifeevents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/lifeevents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/lifeevents/{lifeevent}`

`PATCH api/lifeevents/{lifeevent}`


<!-- END_66abc6c555a8b77d16241c511daa557a -->

<!-- START_c2b86ab3c10a13c7cab82c590aa36f46 -->
## Destroy the life event.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/lifeevents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/lifeevents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/lifeevents/{lifeevent}`


<!-- END_c2b86ab3c10a13c7cab82c590aa36f46 -->

#Messages


<!-- START_85aaf03897c57c83f214cfb0d3f57117 -->
## Store the message.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/api/conversations/1/messages" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1/messages"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/conversations/{conversation}/messages`


<!-- END_85aaf03897c57c83f214cfb0d3f57117 -->

<!-- START_6ebd404556937c16489f98dfe1391176 -->
## Update the message.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/api/conversations/1/messages/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1/messages/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/conversations/{conversation}/messages/{message}`

`PATCH api/conversations/{conversation}/messages/{message}`


<!-- END_6ebd404556937c16489f98dfe1391176 -->

<!-- START_cc05928690c5c137d9d47f0fa2b9d3fe -->
## Destroy the message.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/conversations/1/messages/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/conversations/1/messages/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/conversations/{conversation}/messages/{message}`


<!-- END_cc05928690c5c137d9d47f0fa2b9d3fe -->

#Misc


<!-- START_8ff05e238f60fee26c8b9f40731fa11f -->
## Get the list of general, public statistics.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/statistics" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/statistics"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/compliance" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/compliance"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/compliance/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/compliance/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/currencies" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/currencies"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/currencies/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/currencies/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/countries" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/countries"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/notes`


<!-- END_fc4b6ae244ae158e33e19e0d56b80711 -->

<!-- START_7c30ddc7968295f3665e9e7e4712506d -->
## Get the detail of a given note.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/notes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/notes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/notes/{note}`

`PATCH api/notes/{note}`


<!-- END_e17ba28433f1ed23cb7a68ebbfcafa11 -->

<!-- START_9541e0368a1f31ee173647e886e97f45 -->
## Delete a note.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/notes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/notes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/notes/{note}`


<!-- END_9541e0368a1f31ee173647e886e97f45 -->

<!-- START_fdd8df9779f2ebe397ed5e6a2ff7473b -->
## Get the list of notes for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

#Occupations


<!-- START_44fc9d2f31e34bec296d01a4fd3b4f64 -->
## Get the list of occupations.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/occupations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/occupations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/occupations`


<!-- END_94a278dfa6b18e34c60ccc41fdf72fbb -->

<!-- START_1a5156301ff2f3cd6b0816ee2303331d -->
## Get the detail of a given occupation.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/occupations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/occupations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/occupations/{occupation}`

`PATCH api/occupations/{occupation}`


<!-- END_9b48b3be226fa34bfad64e60ea8b915f -->

<!-- START_55ce54d26a88d070e333a42baabef95d -->
## Delete an occupation.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/occupations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/occupations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/occupations/{occupation}`


<!-- END_55ce54d26a88d070e333a42baabef95d -->

#Pets


<!-- START_e0c1b7b4dea17c1d1ff6c3b57f7f4082 -->
## Get the list of pet.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/pets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/pets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/pets`


<!-- END_cbb5a5c7bc1cde4598c0a554fa1ed829 -->

<!-- START_7f555f395aff7f195f4c5b5c11d33ccd -->
## Get the detail of a given pet.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/pets/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/pets/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/pets/{pet}`

`PATCH api/pets/{pet}`


<!-- END_d20e803533b81c7387387db2c9038e98 -->

<!-- START_f64f4b8a970dc8ac4a08d29fe696cc37 -->
## Delete a pet.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/pets/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/pets/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/pets/{pet}`


<!-- END_f64f4b8a970dc8ac4a08d29fe696cc37 -->

<!-- START_b61bb60b2c9acc1193b64a81630077d2 -->
## Get the list of pets for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/pets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/pets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/places" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/places" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/places`


<!-- END_4c3e06c510713e3a9090bbf1c008203a -->

<!-- START_dd9635f21ef0081d95c38071781376f5 -->
## Get the detail of a given place.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/places/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/places/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/places/{place}`

`PATCH api/places/{place}`


<!-- END_38859726f694afa752fe160e8c4920da -->

<!-- START_90449332adbee73e240f8ef672543a3a -->
## Delete a place.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/places/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/places/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/places/{place}`


<!-- END_90449332adbee73e240f8ef672543a3a -->

#Relationship Type Groups


<!-- START_01117b30a8e4332606627a000667d176 -->
## Get all relationship type groups in an instance.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationshiptypegroups" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationshiptypegroups"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationshiptypegroups/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationshiptypegroups/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationshiptypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationshiptypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationshiptypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationshiptypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/relationships" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationships"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/relationships`


<!-- END_36a3b4ac5edc618fd2104f7029c7849a -->

<!-- START_211bdcc77418eb92671e24e623788444 -->
## Get the detail of a given relationship.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/relationships/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationships/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/relationships/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationships/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/relationships/{relationship}`

`PATCH api/relationships/{relationship}`


<!-- END_533074e3799d9ee6ec97d4a3d4f1b46b -->

<!-- START_0151793b11bff7a72c0b4c7eabf2288f -->
## Delete a relationship.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/relationships/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/relationships/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/relationships/{relationship}`


<!-- END_0151793b11bff7a72c0b4c7eabf2288f -->

<!-- START_033b0df90343be6d8201ca1ab91a16c1 -->
## Get all of relationships of a contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/relationships" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/relationships"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/reminders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/reminders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/reminders`


<!-- END_0984ac5a08321d0fb88aaaa1789ec5eb -->

<!-- START_5eb56fa8f5b62926fa180c5662a4eb1b -->
## Get the detail of a given reminder.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/reminders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/reminders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/reminders/{reminder}`

`PATCH api/reminders/{reminder}`


<!-- END_d50c1638768ef084cd00d5a9bde204c0 -->

<!-- START_fd37f2a81bb0f911528f9b10f8759a59 -->
## Delete a reminder.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/reminders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/reminders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/reminders/{reminder}`


<!-- END_fd37f2a81bb0f911528f9b10f8759a59 -->

<!-- START_085928bc6ada4c42f7e31e8cea93dda1 -->
## Get the list of reminders for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/reminders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/reminders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/contacts/1/setTags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/setTags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/contacts/{contact}/setTags`


<!-- END_d0ed144b117d0ed3111aab4cd1ffd25a -->

<!-- START_128ee14ecacc3cf46357b198e753176a -->
## Remove all the tags associated with the contact.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/api/contacts/1/unsetTags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/unsetTags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/contacts/{contact}/unsetTags`


<!-- END_128ee14ecacc3cf46357b198e753176a -->

<!-- START_f362d4ee3f66ba2111a83c58682e246f -->
## Remove one or more specific tags associated with the contact.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/api/contacts/1/unsetTag" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/unsetTag"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/contacts/{contact}/unsetTag`


<!-- END_f362d4ee3f66ba2111a83c58682e246f -->

<!-- START_dde6989ab5551d4fb09439f7cb2554c5 -->
## Get the list of the contacts.

We will only retrieve the contacts that are "real", not the partials
ones.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/tags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/tags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/tags`


<!-- END_6b95d7d1e0e5c34dd24d290bc715dccb -->

<!-- START_faf1227d26e1a9f94cda4bb6a688c251 -->
## Get the detail of a given tag.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/tags/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/tags/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/tags/{tag}`

`PATCH api/tags/{tag}`


<!-- END_a4a8a57c60acc8638ef566d690878aca -->

<!-- START_4a7e5df1eb6e21ac01a7ea9dbd9bf710 -->
## Delete a tag.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/tags/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tags/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/tags/{tag}`


<!-- END_4a7e5df1eb6e21ac01a7ea9dbd9bf710 -->

#Tasks


<!-- START_4227b9e5e54912af051e8dd5472afbce -->
## Get the list of task.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/tasks`


<!-- END_4da0d9b378428dcc89ced395d4a806e7 -->

<!-- START_5297efa151ae4fd515fec2efd5cb1e9a -->
## Get the detail of a given task.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/tasks/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X PUT \
    "https://monicalocal.test/api/tasks/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT api/tasks/{task}`

`PATCH api/tasks/{task}`


<!-- END_546f027bf591f2ef4a8a743f0a59051d -->

<!-- START_8b8069956f22facfa8cdc67aece156a8 -->
## Delete a task.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/api/tasks/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/tasks/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE api/tasks/{task}`


<!-- END_8b8069956f22facfa8cdc67aece156a8 -->

<!-- START_c6eeee5f958cc1a8c98fb86041d52f06 -->
## Get the list of tasks for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api/contacts/1/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/contacts/1/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/me" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/me"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/me/compliance" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/me/compliance"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X GET \
    -G "https://monicalocal.test/api/me/compliance/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/me/compliance/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/api/me/compliance" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api/me/compliance"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST api/me/compliance`


<!-- END_93263693603c69f0172b2474dd65074c -->

#general


<!-- START_df601fa16fbaec26f87fb38967071b66 -->
## File flag action.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/pragmarx/countries/flag/file/1.svg" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/pragmarx/countries/flag/file/1.svg"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (500):

```json
{
    "message": "The file \"\/home\/asbin\/devel\/monica\/monica\/vendor\/pragmarx\/countries\/src\/data\/flags\/.svg\" does not exist"
}
```

### HTTP Request
`GET pragmarx/countries/flag/file/{cca3}.svg`


<!-- END_df601fa16fbaec26f87fb38967071b66 -->

<!-- START_b7319d5ae5a92a7f6348583003629b9b -->
## Download flag action.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/pragmarx/countries/flag/download/1.svg" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/pragmarx/countries/flag/download/1.svg"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (500):

```json
{
    "message": "The file \"\/home\/asbin\/devel\/monica\/monica\/vendor\/pragmarx\/countries\/src\/data\/flags\/.svg\" does not exist"
}
```

### HTTP Request
`GET pragmarx/countries/flag/download/{cca3}.svg`


<!-- END_b7319d5ae5a92a7f6348583003629b9b -->

<!-- START_22786cffefa26cc6382448935f6d1ea0 -->
## Show the login Webauthn request after a login authentication.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/webauthn/auth" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/webauthn/auth"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET webauthn/auth`


<!-- END_22786cffefa26cc6382448935f6d1ea0 -->

<!-- START_0dcb6f8a59f11c87085506b46976cdaa -->
## Authenticate a webauthn request.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/webauthn/auth" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/webauthn/auth"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST webauthn/auth`


<!-- END_0dcb6f8a59f11c87085506b46976cdaa -->

<!-- START_96e4b3ef127cb0831a67e08757bd00ec -->
## Return the register data to attempt a Webauthn registration.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/webauthn/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/webauthn/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET webauthn/register`


<!-- END_96e4b3ef127cb0831a67e08757bd00ec -->

<!-- START_61aaf668241de257a881c5cc1ad54391 -->
## Validate and create the Webauthn request.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/webauthn/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/webauthn/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST webauthn/register`


<!-- END_61aaf668241de257a881c5cc1ad54391 -->

<!-- START_0d9596ca730c1107eeb32baf83d96c09 -->
## Remove an existing Webauthn key.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/webauthn/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/webauthn/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE webauthn/{id}`


<!-- END_0d9596ca730c1107eeb32baf83d96c09 -->

<!-- START_c6c5c00d6ac7f771f157dff4a2889b1a -->
## _debugbar/open
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/_debugbar/open" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_debugbar/open"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (404):

```json
{
    "message": ""
}
```

### HTTP Request
`GET _debugbar/open`


<!-- END_c6c5c00d6ac7f771f157dff4a2889b1a -->

<!-- START_7b167949c615f4a7e7b673f8d5fdaf59 -->
## Return Clockwork output

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/_debugbar/clockwork/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_debugbar/clockwork/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (404):

```json
{
    "message": ""
}
```

### HTTP Request
`GET _debugbar/clockwork/{id}`


<!-- END_7b167949c615f4a7e7b673f8d5fdaf59 -->

<!-- START_01a252c50bd17b20340dbc5a91cea4b7 -->
## _debugbar/telescope/{id}
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/_debugbar/telescope/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_debugbar/telescope/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (404):

```json
{
    "message": ""
}
```

### HTTP Request
`GET _debugbar/telescope/{id}`


<!-- END_01a252c50bd17b20340dbc5a91cea4b7 -->

<!-- START_5f8a640000f5db43332951f0d77378c4 -->
## Return the stylesheets for the Debugbar

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/_debugbar/assets/stylesheets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_debugbar/assets/stylesheets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (404):

```json
{
    "message": ""
}
```

### HTTP Request
`GET _debugbar/assets/stylesheets`


<!-- END_5f8a640000f5db43332951f0d77378c4 -->

<!-- START_db7a887cf930ce3c638a8708fd1a75ee -->
## Return the javascript for the Debugbar

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/_debugbar/assets/javascript" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_debugbar/assets/javascript"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (404):

```json
{
    "message": ""
}
```

### HTTP Request
`GET _debugbar/assets/javascript`


<!-- END_db7a887cf930ce3c638a8708fd1a75ee -->

<!-- START_0973671c4f56e7409202dc85c868d442 -->
## Forget a cache key

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/_debugbar/cache/1/" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_debugbar/cache/1/"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE _debugbar/cache/{key}/{tags?}`


<!-- END_0973671c4f56e7409202dc85c868d442 -->

<!-- START_5872faed3feb2d483747b62c0b3e6914 -->
## u2f/register
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/u2f/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/u2f/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET u2f/register`


<!-- END_5872faed3feb2d483747b62c0b3e6914 -->

<!-- START_40ba359a51d1a713c1bd373803bdce7e -->
## u2f/register
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/u2f/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/u2f/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST u2f/register`


<!-- END_40ba359a51d1a713c1bd373803bdce7e -->

<!-- START_bbda0da09702620ebf6007219afc8d6a -->
## u2f/auth
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/u2f/auth" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/u2f/auth"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET u2f/auth`


<!-- END_bbda0da09702620ebf6007219afc8d6a -->

<!-- START_b99f3e3b6d4cffc9b62371757a5070bb -->
## u2f/auth
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/u2f/auth" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/u2f/auth"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST u2f/auth`


<!-- END_b99f3e3b6d4cffc9b62371757a5070bb -->

<!-- START_e4c20ab9c4727524c3daa74a53e56200 -->
## Display the form to gather additional payment verification for the given payment.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/stripe/payment/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/stripe/payment/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (500):

```json
{
    "message": "No such payment_intent: 1"
}
```

### HTTP Request
`GET stripe/payment/{id}`


<!-- END_e4c20ab9c4727524c3daa74a53e56200 -->

<!-- START_15ae8ca17c014b55868e68dc48ee5047 -->
## Handle a Stripe webhook call.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/stripe/webhook" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/stripe/webhook"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST stripe/webhook`


<!-- END_15ae8ca17c014b55868e68dc48ee5047 -->

<!-- START_41d2f7697c6118f36f8b676e5bd19ea0 -->
## Login using the given user ID / email.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/_dusk/login/1/" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_dusk/login/1/"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`GET _dusk/login/{userId}/{guard?}`


<!-- END_41d2f7697c6118f36f8b676e5bd19ea0 -->

<!-- START_6375e7300024aae0f6af283b4a72cb1b -->
## Log the user out of the application.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/_dusk/logout/" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_dusk/logout/"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`GET _dusk/logout/{guard?}`


<!-- END_6375e7300024aae0f6af283b4a72cb1b -->

<!-- START_09dcf3e9a9b6585fa40e4ead6c3c858a -->
## Retrieve the authenticated user identifier and class name.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/_dusk/user/" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/_dusk/user/"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
[]
```

### HTTP Request
`GET _dusk/user/{guard?}`


<!-- END_09dcf3e9a9b6585fa40e4ead6c3c858a -->

<!-- START_93ea5dc447bb42e673cefd0a4726fe21 -->
## Display the specified resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/dav/" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/dav/"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
    "message": "Invalid credentials."
}
```

### HTTP Request
`GET dav/{path?}`

`POST dav/{path?}`

`PUT dav/{path?}`

`PATCH dav/{path?}`

`DELETE dav/{path?}`

`OPTIONS dav/{path?}`

`GET dav/{path?}`

`POST dav/{path?}`

`PUT dav/{path?}`

`PATCH dav/{path?}`

`DELETE dav/{path?}`

`PROPFIND dav/{path?}`

`PROPPATCH dav/{path?}`

`MKCOL dav/{path?}`

`COPY dav/{path?}`

`MOVE dav/{path?}`

`LOCK dav/{path?}`

`UNLOCK dav/{path?}`

`OPTIONS dav/{path?}`

`REPORT dav/{path?}`


<!-- END_93ea5dc447bb42e673cefd0a4726fe21 -->

<!-- START_0c068b4037fb2e47e71bd44bd36e3e2a -->
## Authorize a client to access the user&#039;s account.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/oauth/authorize" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/authorize"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET oauth/authorize`


<!-- END_0c068b4037fb2e47e71bd44bd36e3e2a -->

<!-- START_e48cc6a0b45dd21b7076ab2c03908687 -->
## Approve the authorization request.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/oauth/authorize" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/authorize"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST oauth/authorize`


<!-- END_e48cc6a0b45dd21b7076ab2c03908687 -->

<!-- START_de5d7581ef1275fce2a229b6b6eaad9c -->
## Deny the authorization request.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/oauth/authorize" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/authorize"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE oauth/authorize`


<!-- END_de5d7581ef1275fce2a229b6b6eaad9c -->

<!-- START_a09d20357336aa979ecd8e3972ac9168 -->
## Authorize a client to access the user&#039;s account.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/oauth/token" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/token"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST oauth/token`


<!-- END_a09d20357336aa979ecd8e3972ac9168 -->

<!-- START_d6a56149547e03307199e39e03e12d1c -->
## Get all of the authorized tokens for the authenticated user.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/oauth/tokens" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/tokens"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET oauth/tokens`


<!-- END_d6a56149547e03307199e39e03e12d1c -->

<!-- START_a9a802c25737cca5324125e5f60b72a5 -->
## Delete the given token.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/oauth/tokens/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/tokens/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE oauth/tokens/{token_id}`


<!-- END_a9a802c25737cca5324125e5f60b72a5 -->

<!-- START_abe905e69f5d002aa7d26f433676d623 -->
## Get a fresh transient token cookie for the authenticated user.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/oauth/token/refresh" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/token/refresh"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST oauth/token/refresh`


<!-- END_abe905e69f5d002aa7d26f433676d623 -->

<!-- START_babcfe12d87b8708f5985e9d39ba8f2c -->
## Get all of the clients for the authenticated user.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/oauth/clients" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/clients"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET oauth/clients`


<!-- END_babcfe12d87b8708f5985e9d39ba8f2c -->

<!-- START_9eabf8d6e4ab449c24c503fcb42fba82 -->
## Store a new client.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/oauth/clients" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/clients"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST oauth/clients`


<!-- END_9eabf8d6e4ab449c24c503fcb42fba82 -->

<!-- START_784aec390a455073fc7464335c1defa1 -->
## Update the given client.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/oauth/clients/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/clients/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT oauth/clients/{client_id}`


<!-- END_784aec390a455073fc7464335c1defa1 -->

<!-- START_1f65a511dd86ba0541d7ba13ca57e364 -->
## Delete the given client.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/oauth/clients/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/clients/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE oauth/clients/{client_id}`


<!-- END_1f65a511dd86ba0541d7ba13ca57e364 -->

<!-- START_9e281bd3a1eb1d9eb63190c8effb607c -->
## Get all of the available scopes for the application.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/oauth/scopes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/scopes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET oauth/scopes`


<!-- END_9e281bd3a1eb1d9eb63190c8effb607c -->

<!-- START_9b2a7699ce6214a79e0fd8107f8b1c9e -->
## Get all of the personal access tokens for the authenticated user.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/oauth/personal-access-tokens" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/personal-access-tokens"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET oauth/personal-access-tokens`


<!-- END_9b2a7699ce6214a79e0fd8107f8b1c9e -->

<!-- START_a8dd9c0a5583742e671711f9bb3ee406 -->
## Create a new personal access token for the user.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/oauth/personal-access-tokens" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/personal-access-tokens"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST oauth/personal-access-tokens`


<!-- END_a8dd9c0a5583742e671711f9bb3ee406 -->

<!-- START_bae65df80fd9d72a01439241a9ea20d0 -->
## Delete the given token.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/oauth/personal-access-tokens/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/personal-access-tokens/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE oauth/personal-access-tokens/{token_id}`


<!-- END_bae65df80fd9d72a01439241a9ea20d0 -->

<!-- START_ae309226f6476a5c4acc7fb3419990bd -->
## Default request to the API.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/api" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/api"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET api`


<!-- END_ae309226f6476a5c4acc7fb3419990bd -->

<!-- START_53be1e9e10a08458929a2e0ea70ddb86 -->
## /
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET /`


<!-- END_53be1e9e10a08458929a2e0ea70ddb86 -->

<!-- START_66e08d3cc8222573018fed49e121e96d -->
## Show the application&#039;s login form.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/login"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET login`


<!-- END_66e08d3cc8222573018fed49e121e96d -->

<!-- START_ba35aa39474cb98cfb31829e70eb8b74 -->
## Handle a login request to the application.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/login"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST login`


<!-- END_ba35aa39474cb98cfb31829e70eb8b74 -->

<!-- START_e65925f23b9bc6b93d9356895f29f80c -->
## Log the user out of the application.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/logout" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/logout"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST logout`


<!-- END_e65925f23b9bc6b93d9356895f29f80c -->

<!-- START_ff38dfb1bd1bb7e1aa24b4e1792a9768 -->
## Show the application registration form.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET register`


<!-- END_ff38dfb1bd1bb7e1aa24b4e1792a9768 -->

<!-- START_d7aad7b5ac127700500280d511a3db01 -->
## Handle a registration request for the application.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST register`


<!-- END_d7aad7b5ac127700500280d511a3db01 -->

<!-- START_d72797bae6d0b1f3a341ebb1f8900441 -->
## Display the form to request a password reset link.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/password/reset" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/password/reset"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET password/reset`


<!-- END_d72797bae6d0b1f3a341ebb1f8900441 -->

<!-- START_feb40f06a93c80d742181b6ffb6b734e -->
## Send a reset link to the given user.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/password/email" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/password/email"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST password/email`


<!-- END_feb40f06a93c80d742181b6ffb6b734e -->

<!-- START_e1605a6e5ceee9d1aeb7729216635fd7 -->
## Display the password reset view for the given token.

If no token is present, display the link request form.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/password/reset/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/password/reset/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET password/reset/{token}`


<!-- END_e1605a6e5ceee9d1aeb7729216635fd7 -->

<!-- START_cafb407b7a846b31491f97719bb15aef -->
## Reset the given user&#039;s password.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/password/reset" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/password/reset"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST password/reset`


<!-- END_cafb407b7a846b31491f97719bb15aef -->

<!-- START_c88fc6aa6eb1bee7a494d3c0a02038b1 -->
## Show the email verification notice.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/email/verify" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/email/verify"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET email/verify`


<!-- END_c88fc6aa6eb1bee7a494d3c0a02038b1 -->

<!-- START_6792598c74b34a271a2e3ab9365adf9e -->
## Mark the authenticated user&#039;s email address as verified.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/email/verify/1/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/email/verify/1/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET email/verify/{id}/{hash}`


<!-- END_6792598c74b34a271a2e3ab9365adf9e -->

<!-- START_38334d357e7e155bf70b9ab94619ca3d -->
## Resend the email verification notification.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/email/resend" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/email/resend"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST email/resend`


<!-- END_38334d357e7e155bf70b9ab94619ca3d -->

<!-- START_d0da0ccc9135ef2191e53419e0d27897 -->
## Display the specified resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/invitations/accept/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/invitations/accept/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (404):

```json
{
    "message": "No query results for model [App\\Models\\Account\\Invitation]."
}
```

### HTTP Request
`GET invitations/accept/{key}`


<!-- END_d0da0ccc9135ef2191e53419e0d27897 -->

<!-- START_3afe36cd8aee12f9f9a70e0bfed54e02 -->
## Store the specified resource.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/invitations/accept/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/invitations/accept/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST invitations/accept/{key}`


<!-- END_3afe36cd8aee12f9f9a70e0bfed54e02 -->

<!-- START_568bd749946744d2753eaad6cfad5db6 -->
## Log the user out of the application.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/logout" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/logout"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET logout`


<!-- END_568bd749946744d2753eaad6cfad5db6 -->

<!-- START_ea35b4448f9f4cbfab667e1b8adb971d -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/auth/login-recovery" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/auth/login-recovery"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET auth/login-recovery`


<!-- END_ea35b4448f9f4cbfab667e1b8adb971d -->

<!-- START_1badf650a84de17f1c9d7a6de3e116a3 -->
## Validate recovery login.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/auth/login-recovery" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/auth/login-recovery"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST auth/login-recovery`


<!-- END_1badf650a84de17f1c9d7a6de3e116a3 -->

<!-- START_bd22711830ebeb8d5a1b1ba72e319a03 -->
## Redirect the user after 2fa form has been submitted.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/validate2fa" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/validate2fa"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST validate2fa`


<!-- END_bd22711830ebeb8d5a1b1ba72e319a03 -->

<!-- START_30059a09ef3f0284c40e4d06962ce08d -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/dashboard" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/dashboard"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET dashboard`


<!-- END_30059a09ef3f0284c40e4d06962ce08d -->

<!-- START_88d42416743ddaaa2aedd169a6a28302 -->
## Get calls for the dashboard.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/dashboard/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/dashboard/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET dashboard/calls`


<!-- END_88d42416743ddaaa2aedd169a6a28302 -->

<!-- START_ba687fa01678eba22c8edc2255fcc8a5 -->
## Get notes for the dashboard.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/dashboard/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/dashboard/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET dashboard/notes`


<!-- END_ba687fa01678eba22c8edc2255fcc8a5 -->

<!-- START_2098e830a1bff498674f0f2a1a624f3f -->
## Get debts for the dashboard.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/dashboard/debts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/dashboard/debts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET dashboard/debts`


<!-- END_2098e830a1bff498674f0f2a1a624f3f -->

<!-- START_d011b8b25ae03c5994e56554385fab7d -->
## Save the current active tab to the User table.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/dashboard/setTab" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/dashboard/setTab"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST dashboard/setTab`


<!-- END_d011b8b25ae03c5994e56554385fab7d -->

<!-- START_541ad2da8fb7d187e555ffaf9519d60f -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/compliance" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/compliance"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET compliance`


<!-- END_541ad2da8fb7d187e555ffaf9519d60f -->

<!-- START_6d5fce3b9f44f4ffd01da41d6222b018 -->
## compliance/sign
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/compliance/sign" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/compliance/sign"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST compliance/sign`


<!-- END_6d5fce3b9f44f4ffd01da41d6222b018 -->

<!-- START_407aa6d67cef2267b4a9b6044ca9a433 -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/changelog" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/changelog"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET changelog`


<!-- END_407aa6d67cef2267b4a9b6044ca9a433 -->

<!-- START_7bbf86e85d935d026ccafc231e13baaa -->
## Get the list of primary emotions.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/emotions" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/emotions"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET emotions`


<!-- END_7bbf86e85d935d026ccafc231e13baaa -->

<!-- START_24cf0e0ba8fc5ec8725db3e17d75e1e7 -->
## Get the list of secondary emotions.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/emotions/primaries/1/secondaries" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/emotions/primaries/1/secondaries"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET emotions/primaries/{emotion}/secondaries`


<!-- END_24cf0e0ba8fc5ec8725db3e17d75e1e7 -->

<!-- START_ffd64c3511efc5b784df03a0c35a83af -->
## Get the list of emotions.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/emotions/primaries/1/secondaries/1/emotions" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/emotions/primaries/1/secondaries/1/emotions"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET emotions/primaries/{emotion}/secondaries/{secondaryEmotion}/emotions`


<!-- END_ffd64c3511efc5b784df03a0c35a83af -->

<!-- START_0337f52565742162057a0d63027076cc -->
## Show the form in case the contact is missing.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/notfound" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/notfound"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/notfound`


<!-- END_0337f52565742162057a0d63027076cc -->

<!-- START_dda3172492cbe3d8883b81ce3a6515e0 -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/archived" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/archived"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/archived`


<!-- END_dda3172492cbe3d8883b81ce3a6515e0 -->

<!-- START_0c9a6398dd41a9a88fd7002a73171c49 -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people`


<!-- END_0c9a6398dd41a9a88fd7002a73171c49 -->

<!-- START_4ff531aa5716d0026020e48de423432f -->
## Show the form to add a new contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/add" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/add"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/add`


<!-- END_4ff531aa5716d0026020e48de423432f -->

<!-- START_8627f6a1f47a152cd990181227d151b0 -->
## Display the list of contacts.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/list" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/list"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/list`


<!-- END_8627f6a1f47a152cd990181227d151b0 -->

<!-- START_7d6a5b283dd36aeed06d26c0a609db86 -->
## Store the contact.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people`


<!-- END_7d6a5b283dd36aeed06d26c0a609db86 -->

<!-- START_5920ea31b3ac6a3a2486411f5822e08a -->
## Display the contact profile.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}`


<!-- END_5920ea31b3ac6a3a2486411f5822e08a -->

<!-- START_9a60349fd68c73930ee68cdf0eb7b1a9 -->
## Display the Edit people&#039;s view.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/edit`


<!-- END_9a60349fd68c73930ee68cdf0eb7b1a9 -->

<!-- START_f5c610f05bc99f88e7ce921c00789cbf -->
## Update the contact.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}`


<!-- END_f5c610f05bc99f88e7ce921c00789cbf -->

<!-- START_0db2335e2f44101e85f894fe784be023 -->
## Delete the contact.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}`


<!-- END_0db2335e2f44101e85f894fe784be023 -->

<!-- START_e4c838391c6e4c8aee673bcc47070c42 -->
## Display the Edit avatar screen.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/avatar" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/avatar"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/avatar`


<!-- END_e4c838391c6e4c8aee673bcc47070c42 -->

<!-- START_c1d1534823882ce967ab83b197b7116b -->
## Update the avatar of the contact.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/avatar" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/avatar"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/avatar`


<!-- END_c1d1534823882ce967ab83b197b7116b -->

<!-- START_dc12b81ce74e7fd3a7edf8c45efbad4e -->
## Set the given photo as avatar.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/makeProfilePicture/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/makeProfilePicture/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/makeProfilePicture/{photo}`


<!-- END_dc12b81ce74e7fd3a7edf8c45efbad4e -->

<!-- START_bf05f5a64e806c89cfa2f7c8ed92075a -->
## Display the list of life events.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/lifeevents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/lifeevents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/lifeevents`


<!-- END_bf05f5a64e806c89cfa2f7c8ed92075a -->

<!-- START_05f8b2207eaa6b636eae7efd43763d90 -->
## Get the list of life event categories.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/lifeevents/categories" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/lifeevents/categories"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET lifeevents/categories`


<!-- END_05f8b2207eaa6b636eae7efd43763d90 -->

<!-- START_45b28ffcdd4fb857c4488ef77f042ba6 -->
## Get the list of life event types for a given life event category.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/lifeevents/categories/1/types" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/lifeevents/categories/1/types"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET lifeevents/categories/{lifeEventCategory}/types`


<!-- END_45b28ffcdd4fb857c4488ef77f042ba6 -->

<!-- START_08aa8f6a1522ac68306522a56f005a65 -->
## Store the life event.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/lifeevents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/lifeevents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/lifeevents`


<!-- END_08aa8f6a1522ac68306522a56f005a65 -->

<!-- START_d6ddc14b14543545dfdcfa00b5133d96 -->
## Destroy the life event.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/lifeevents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/lifeevents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE lifeevents/{lifeEvent}`


<!-- END_d6ddc14b14543545dfdcfa00b5133d96 -->

<!-- START_8b19916446551b46d2c88468e739d947 -->
## Get all the contact information for this contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/contactfield" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/contactfield"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/contactfield`


<!-- END_8b19916446551b46d2c88468e739d947 -->

<!-- START_1b94f77d7e2238d145ede1861756c712 -->
## Store the contact field.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/contactfield" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/contactfield"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/contactfield`


<!-- END_1b94f77d7e2238d145ede1861756c712 -->

<!-- START_95622e71c5d011eb3941b65e974ec2ef -->
## Edit the contact field.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/contactfield/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/contactfield/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/contactfield/{contactField}`


<!-- END_95622e71c5d011eb3941b65e974ec2ef -->

<!-- START_961dfb30c3be94823468eeaa8a37a77a -->
## people/{contact}/contactfield/{contactField}
> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/contactfield/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/contactfield/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/contactfield/{contactField}`


<!-- END_961dfb30c3be94823468eeaa8a37a77a -->

<!-- START_26642937b9c37a5936339f74f9735fc0 -->
## Get all the contact field types.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/contactfieldtypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/contactfieldtypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/contactfieldtypes`


<!-- END_26642937b9c37a5936339f74f9735fc0 -->

<!-- START_970cc4027e89729066dbd0d39be799bb -->
## Download the contact as vCard.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/vcard" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/vcard"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/vcard`


<!-- END_970cc4027e89729066dbd0d39be799bb -->

<!-- START_c1c192be6869849e72ede138e91bd809 -->
## Get all the countries.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/countries" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/countries"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET countries`


<!-- END_c1c192be6869849e72ede138e91bd809 -->

<!-- START_b932ba970cf7a2d31ff9a6eae1870243 -->
## Get all the addresses for this contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/addresses" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/addresses"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/addresses`


<!-- END_b932ba970cf7a2d31ff9a6eae1870243 -->

<!-- START_926ab2a0f4d7d6edbd1a3e19f4bb81ee -->
## Store the address.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/addresses" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/addresses"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/addresses`


<!-- END_926ab2a0f4d7d6edbd1a3e19f4bb81ee -->

<!-- START_e4d567bd2e104b05a1de917bc0e7ada8 -->
## Edit the contact field.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/addresses/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/addresses/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/addresses/{address}`


<!-- END_e4d567bd2e104b05a1de917bc0e7ada8 -->

<!-- START_2a30e877cfe2409fff1281c827369aa9 -->
## Destroy the address.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/addresses/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/addresses/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/addresses/{address}`


<!-- END_2a30e877cfe2409fff1281c827369aa9 -->

<!-- START_96b4c2ec62f38e747cb54fcd3d060b14 -->
## Show the Edit work view.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/work/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/work/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/work/edit`


<!-- END_96b4c2ec62f38e747cb54fcd3d060b14 -->

<!-- START_2e2a4c11c35a257af9c89131267af255 -->
## Save the work information.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/work/update" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/work/update"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/work/update`


<!-- END_2e2a4c11c35a257af9c89131267af255 -->

<!-- START_c049dd79dedfc4904169b27a8e3b32c5 -->
## Show the form for editing the specified resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/introductions/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/introductions/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/introductions/edit`


<!-- END_c049dd79dedfc4904169b27a8e3b32c5 -->

<!-- START_8c858f665cfc58ece0ab107fd0727465 -->
## Update the specified resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/introductions/update" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/introductions/update"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/introductions/update`


<!-- END_8c858f665cfc58ece0ab107fd0727465 -->

<!-- START_30d968676edf7307d991000be4f07042 -->
## Get the list of all the tags in the account.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/tags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/tags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET tags`


<!-- END_30d968676edf7307d991000be4f07042 -->

<!-- START_02fafa1b97f2c6ea039658a0d7417d5e -->
## Get the list of all the tags for this contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/tags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/tags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/tags`


<!-- END_02fafa1b97f2c6ea039658a0d7417d5e -->

<!-- START_20e2afdba51fa5adb0c59993ca9f0293 -->
## Update the specified resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/tags/update" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/tags/update"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/tags/update`


<!-- END_20e2afdba51fa5adb0c59993ca9f0293 -->

<!-- START_2a4f9b5b7bd2885324f018d8116b131a -->
## Get all the tasks of this contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/notes`


<!-- END_2a4f9b5b7bd2885324f018d8116b131a -->

<!-- START_afc3d26c8930fe08ddb5e62cdc308ed1 -->
## Store the task.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/notes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/notes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/notes`


<!-- END_afc3d26c8930fe08ddb5e62cdc308ed1 -->

<!-- START_88f08ba1144b136fbe00c1a5119614c7 -->
## Update the specified resource in storage.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/notes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/notes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/notes/{note}`

`PATCH people/{contact}/notes/{note}`


<!-- END_88f08ba1144b136fbe00c1a5119614c7 -->

<!-- START_b837e152d48b240893112ad14db054f9 -->
## Remove the specified resource from storage.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/notes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/notes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/notes/{note}`


<!-- END_b837e152d48b240893112ad14db054f9 -->

<!-- START_78e487be23b2ffc90c451655d2b489ac -->
## people/{contact}/notes/{note}/toggle
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/notes/1/toggle" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/notes/1/toggle"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/notes/{note}/toggle`


<!-- END_78e487be23b2ffc90c451655d2b489ac -->

<!-- START_9daea76e30258bbb81dadb90076921c3 -->
## Show the Edit food preferences view.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/food" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/food"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/food`


<!-- END_9daea76e30258bbb81dadb90076921c3 -->

<!-- START_efc879f656cc94701e1cf7d393cd26d8 -->
## Save the food preferences.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/food/save" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/food/save"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/food/save`


<!-- END_efc879f656cc94701e1cf7d393cd26d8 -->

<!-- START_a940519cab162e9a90037bac2b9e9e49 -->
## Display the Create relationship page.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/relationships/create" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/relationships/create"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/relationships/create`


<!-- END_a940519cab162e9a90037bac2b9e9e49 -->

<!-- START_3c091600559a7eb3c4ac5d2dcf9f8a49 -->
## Store a newly created resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/relationships" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/relationships"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/relationships`


<!-- END_3c091600559a7eb3c4ac5d2dcf9f8a49 -->

<!-- START_68270cc6d6e3377a84dcdc81e5ec8f02 -->
## Show the form for editing the specified resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/relationships/1/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/relationships/1/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/relationships/{relationship}/edit`


<!-- END_68270cc6d6e3377a84dcdc81e5ec8f02 -->

<!-- START_7b3a315b06b156eaace6caf917768f9b -->
## Update the specified resource in storage.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/relationships/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/relationships/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/relationships/{relationship}`

`PATCH people/{contact}/relationships/{relationship}`


<!-- END_7b3a315b06b156eaace6caf917768f9b -->

<!-- START_0eb3349e5ab21e4a574cb6d6473be5b6 -->
## Remove the specified resource from storage.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/relationships/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/relationships/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/relationships/{relationship}`


<!-- END_0eb3349e5ab21e4a574cb6d6473be5b6 -->

<!-- START_ffe56d493095487e01ce53ef6f73c681 -->
## Get all the pets for this contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/pets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/pets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/pets`


<!-- END_ffe56d493095487e01ce53ef6f73c681 -->

<!-- START_054e596db85fc578257e72cd106c8123 -->
## Store the pet.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/pets" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/pets"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/pets`


<!-- END_054e596db85fc578257e72cd106c8123 -->

<!-- START_7b2bdf9d676b424165ea78d504b0daa2 -->
## Update the pet.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/pets/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/pets/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/pets/{pet}`

`PATCH people/{contact}/pets/{pet}`


<!-- END_7b2bdf9d676b424165ea78d504b0daa2 -->

<!-- START_a8245317c673deaacf36315035f1c0ff -->
## people/{contact}/pets/{pet}
> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/pets/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/pets/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/pets/{pet}`


<!-- END_a8245317c673deaacf36315035f1c0ff -->

<!-- START_5b41d6582eacbe702ce4fda016727a64 -->
## Get all the pet categories.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/petcategories" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/petcategories"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET petcategories`


<!-- END_5b41d6582eacbe702ce4fda016727a64 -->

<!-- START_23927b66e1225058c572ab709dcfdf84 -->
## Show the form for creating a new reminder.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/reminders/create" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/reminders/create"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/reminders/create`


<!-- END_23927b66e1225058c572ab709dcfdf84 -->

<!-- START_d64505e30ce890e55cfbdb62cf2aa0ed -->
## Store a reminder.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/reminders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/reminders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/reminders`


<!-- END_d64505e30ce890e55cfbdb62cf2aa0ed -->

<!-- START_9c86759ef57a0c4896c4bcf28c6198de -->
## Show the form for editing the specified resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/reminders/1/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/reminders/1/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/reminders/{reminder}/edit`


<!-- END_9c86759ef57a0c4896c4bcf28c6198de -->

<!-- START_9ab3fde0faf0c57548a965907cff71be -->
## Update the reminder.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/reminders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/reminders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/reminders/{reminder}`

`PATCH people/{contact}/reminders/{reminder}`


<!-- END_9ab3fde0faf0c57548a965907cff71be -->

<!-- START_b15f63c4ada9078bb7a496b6e76ee7a6 -->
## Destroy the reminder.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/reminders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/reminders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/reminders/{reminder}`


<!-- END_b15f63c4ada9078bb7a496b6e76ee7a6 -->

<!-- START_dee9722ebb20ef73fee4c8ca627d935b -->
## Get all the tasks of this contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/tasks`


<!-- END_dee9722ebb20ef73fee4c8ca627d935b -->

<!-- START_5922bacedc050788ba01b21cbaff923a -->
## Get the list of tasks for the account.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET tasks`


<!-- END_5922bacedc050788ba01b21cbaff923a -->

<!-- START_71b4e9c93724a8c5c650c51c953e43da -->
## Store a newly created resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/tasks" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/tasks"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST tasks`


<!-- END_71b4e9c93724a8c5c650c51c953e43da -->

<!-- START_a639983fd71cf3e3bdbd24fd814319d2 -->
## Update a task.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/tasks/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/tasks/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT tasks/{task}`

`PATCH tasks/{task}`


<!-- END_a639983fd71cf3e3bdbd24fd814319d2 -->

<!-- START_4cd7e210481a702d15c7807e146d62c9 -->
## Destroy the task.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/tasks/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/tasks/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE tasks/{task}`


<!-- END_4cd7e210481a702d15c7807e146d62c9 -->

<!-- START_f927654f0dc360e3075e83861cbefb76 -->
## List all the gifts for the given contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/gifts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/gifts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/gifts`


<!-- END_f927654f0dc360e3075e83861cbefb76 -->

<!-- START_bdf09243fc43c646d93f7e6fe754a778 -->
## Show the form for creating a new resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/gifts/create" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/gifts/create"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/gifts/create`


<!-- END_bdf09243fc43c646d93f7e6fe754a778 -->

<!-- START_6f7be985188944be5d8723440a4d59de -->
## Store a newly created resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/gifts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/gifts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/gifts`


<!-- END_6f7be985188944be5d8723440a4d59de -->

<!-- START_fc16a16d2ef31425c2a489683f87ed22 -->
## Show the form for editing the specified resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/gifts/1/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/gifts/1/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/gifts/{gift}/edit`


<!-- END_fc16a16d2ef31425c2a489683f87ed22 -->

<!-- START_f5848847119eb7733d180bbf4eb166b2 -->
## Update the specified resource in storage.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/gifts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/gifts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/gifts/{gift}`

`PATCH people/{contact}/gifts/{gift}`


<!-- END_f5848847119eb7733d180bbf4eb166b2 -->

<!-- START_a4cee4d01757a006dedf5f338afa9584 -->
## Remove the specified resource from storage.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/gifts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/gifts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/gifts/{gift}`


<!-- END_a4cee4d01757a006dedf5f338afa9584 -->

<!-- START_f16b439a15e5923f76873da64b02d5db -->
## Mark a gift as being offered.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/gifts/1/toggle" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/gifts/1/toggle"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/gifts/{gift}/toggle`


<!-- END_f16b439a15e5923f76873da64b02d5db -->

<!-- START_fe50de4a99781206f8fc5239210a5ec5 -->
## Show the form for creating a new resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/debts/create" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/debts/create"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/debts/create`


<!-- END_fe50de4a99781206f8fc5239210a5ec5 -->

<!-- START_444a90255b3fd92106d9e356e2346b36 -->
## Store a newly created resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/debts" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/debts"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/debts`


<!-- END_444a90255b3fd92106d9e356e2346b36 -->

<!-- START_c3d64e7762048312f0c668cdcd728af2 -->
## Show the form for editing the specified resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/debts/1/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/debts/1/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/debts/{debt}/edit`


<!-- END_c3d64e7762048312f0c668cdcd728af2 -->

<!-- START_b51ea2cde01c24cb48f9728e6fcec7cf -->
## Update the specified resource in storage.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/debts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/debts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/debts/{debt}`

`PATCH people/{contact}/debts/{debt}`


<!-- END_b51ea2cde01c24cb48f9728e6fcec7cf -->

<!-- START_0b7e3931941abe44f9f031b0dbdd6ac3 -->
## Remove the specified resource from storage.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/debts/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/debts/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/debts/{debt}`


<!-- END_0b7e3931941abe44f9f031b0dbdd6ac3 -->

<!-- START_af6b4f14219c75926e2fe81b575bb706 -->
## Display the list of calls.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/calls`


<!-- END_af6b4f14219c75926e2fe81b575bb706 -->

<!-- START_9df40f0070b7dfb354b727aad2063887 -->
## Store a call.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/calls" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/calls"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/calls`


<!-- END_9df40f0070b7dfb354b727aad2063887 -->

<!-- START_e577cc1c64817e30cb20410c293c09cc -->
## Update a call.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/calls/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/calls/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/calls/{call}`

`PATCH people/{contact}/calls/{call}`


<!-- END_e577cc1c64817e30cb20410c293c09cc -->

<!-- START_40b8ee50a53972968d62fd23c187295b -->
## Delete the call.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/calls/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/calls/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/calls/{call}`


<!-- END_40b8ee50a53972968d62fd23c187295b -->

<!-- START_738d1d4fd27ed494971a2aa8cb941559 -->
## Display the list of conversations.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/conversations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/conversations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/conversations`


<!-- END_738d1d4fd27ed494971a2aa8cb941559 -->

<!-- START_f9fac7096f0de647a107841ef6eabd91 -->
## Display the Create conversation page.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/conversations/create" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/conversations/create"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/conversations/create`


<!-- END_f9fac7096f0de647a107841ef6eabd91 -->

<!-- START_fac84525f83e6e6289aed71488077af3 -->
## Store the conversation.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/conversations" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/conversations"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/conversations`


<!-- END_fac84525f83e6e6289aed71488077af3 -->

<!-- START_4706b108dc808e303f8f31b3bf80f077 -->
## Display a specific conversation.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/conversations/1/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/conversations/1/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/conversations/{conversation}/edit`


<!-- END_4706b108dc808e303f8f31b3bf80f077 -->

<!-- START_dc3dd48293cd34388b0051d97296a2dc -->
## Update the conversation.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/conversations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/conversations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/conversations/{conversation}`

`PATCH people/{contact}/conversations/{conversation}`


<!-- END_dc3dd48293cd34388b0051d97296a2dc -->

<!-- START_482243b5fba934ebba5d69a25189cd27 -->
## Delete the conversation.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/conversations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/conversations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/conversations/{conversation}`


<!-- END_482243b5fba934ebba5d69a25189cd27 -->

<!-- START_c19ed74ec1c0209724c48dddef08be80 -->
## Display the list of documents.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/documents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/documents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/documents`


<!-- END_c19ed74ec1c0209724c48dddef08be80 -->

<!-- START_4695300f7a1054374e27863833d34232 -->
## Store the document.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/documents" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/documents"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/documents`


<!-- END_4695300f7a1054374e27863833d34232 -->

<!-- START_991c449ecc71909c0dadbf1e1b33ea0d -->
## Delete the document.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/documents/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/documents/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/documents/{document}`


<!-- END_991c449ecc71909c0dadbf1e1b33ea0d -->

<!-- START_b2c55f212140ba3becfd9f99c91ecffe -->
## Display the list of photos.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/photos" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/photos"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/photos`


<!-- END_b2c55f212140ba3becfd9f99c91ecffe -->

<!-- START_6cd9497c972ca94710333fe87e2047fd -->
## Store the Photo.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/photos" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/photos"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/photos`


<!-- END_6cd9497c972ca94710333fe87e2047fd -->

<!-- START_70112ed3dc4cddb5b34d8534345a8ec5 -->
## Delete the Photo.

Also, if this photo was the current avatar of the contact, change the
avatar to the default one.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/people/1/photos/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/photos/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE people/{contact}/photos/{photo}`


<!-- END_70112ed3dc4cddb5b34d8534345a8ec5 -->

<!-- START_5752aebaa2f12dd9a4369018643c3517 -->
## Search used in the header.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/search" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/search"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/search`


<!-- END_5752aebaa2f12dd9a4369018643c3517 -->

<!-- START_cc74edc8b9308985b50728f913130e37 -->
## Set or change the frequency of which the user wants to stay in touch with
the given contact.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/stayintouch" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/stayintouch"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/stayintouch`


<!-- END_cc74edc8b9308985b50728f913130e37 -->

<!-- START_8895d5074cac780690a81325088977b5 -->
## Toggle favorites of a contact.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/people/1/favorite" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/favorite"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST people/{contact}/favorite`


<!-- END_8895d5074cac780690a81325088977b5 -->

<!-- START_e2d885dc3679e9a586f0e69333e8121a -->
## Toggle archive state of a contact.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/people/1/archive" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/archive"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT people/{contact}/archive`


<!-- END_e2d885dc3679e9a586f0e69333e8121a -->

<!-- START_ca30f4eba50049d1c5ec181b24b46b12 -->
## Get all the activities for this contact.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/activities" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/activities"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/activities`


<!-- END_ca30f4eba50049d1c5ec181b24b46b12 -->

<!-- START_3b4ffe0181b7695718c4a9e26d2843ae -->
## Get all the activities for this contact for a specific year.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/people/1/activities/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/people/1/activities/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET people/{contact}/activities/{year}`


<!-- END_3b4ffe0181b7695718c4a9e26d2843ae -->

<!-- START_1ddf8e36deaf7ed7c8b0901dc319f17f -->
## Show the form for creating a new resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/activities/add/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/activities/add/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET activities/add/{contact}`


<!-- END_1ddf8e36deaf7ed7c8b0901dc319f17f -->

<!-- START_b9e78b5b093a9095653b562af8a3d44a -->
## Store a newly created resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/activities/store/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/activities/store/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST activities/store/{contact}`


<!-- END_b9e78b5b093a9095653b562af8a3d44a -->

<!-- START_62e677156126770ca4e1e68b2f103c9e -->
## Show the form for editing the specified resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/activities/1/edit/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/activities/1/edit/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET activities/{activity}/edit/{contact}`


<!-- END_62e677156126770ca4e1e68b2f103c9e -->

<!-- START_7309105df1ff18d5bd3f1c8be0ce0715 -->
## Update the specified resource in storage.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/activities/1/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/activities/1/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT activities/{activity}/{contact}`


<!-- END_7309105df1ff18d5bd3f1c8be0ce0715 -->

<!-- START_49c3d85bc13c21dce3e75af60f9df3ad -->
## Remove the specified resource from storage.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/activities/1/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/activities/1/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE activities/{activity}/{contact}`


<!-- END_49c3d85bc13c21dce3e75af60f9df3ad -->

<!-- START_3afefc916e36258a6b06889f1fa78624 -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/journal" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET journal`


<!-- END_3afefc916e36258a6b06889f1fa78624 -->

<!-- START_881143b28ef59b581135df52703fa9e8 -->
## Get all the journal entries.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/journal/entries" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/entries"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET journal/entries`


<!-- END_881143b28ef59b581135df52703fa9e8 -->

<!-- START_9af7f62fa5d160fdab6b1b01b9f7dd0c -->
## Gets the details of a single Journal Entry.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/journal/entries/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/entries/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET journal/entries/{journalEntry}`


<!-- END_9af7f62fa5d160fdab6b1b01b9f7dd0c -->

<!-- START_abac6d2296d9391f8ef9329b7d9aa48f -->
## Indicates whether the user has already rated the current day.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/journal/hasRated" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/hasRated"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET journal/hasRated`


<!-- END_abac6d2296d9391f8ef9329b7d9aa48f -->

<!-- START_1ba4a99de60bdb4f99ebefae47e22dc4 -->
## Store the day entry.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/journal/day" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/day"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST journal/day`


<!-- END_1ba4a99de60bdb4f99ebefae47e22dc4 -->

<!-- START_3caeccabbf16f5097491b8e9304d0941 -->
## Delete the Day entry.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/journal/day/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/day/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE journal/day/{day}`


<!-- END_3caeccabbf16f5097491b8e9304d0941 -->

<!-- START_f237f14ad60e7bae99bbe06435bb78b3 -->
## Display the Create journal entry screen.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/journal/add" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/add"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET journal/add`


<!-- END_f237f14ad60e7bae99bbe06435bb78b3 -->

<!-- START_3714e6b381e39e25ed9ed2ead252c153 -->
## Saves the journal entry.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/journal/create" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/create"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST journal/create`


<!-- END_3714e6b381e39e25ed9ed2ead252c153 -->

<!-- START_2aa06a2568db043b6807d46ca370011c -->
## Display the Edit journal entry screen.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/journal/entries/1/edit" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/entries/1/edit"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET journal/entries/{entry}/edit`


<!-- END_2aa06a2568db043b6807d46ca370011c -->

<!-- START_6284bd09cda5bb788913be974d7a100f -->
## Update a journal entry.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/journal/entries/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/entries/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT journal/entries/{entry}`


<!-- END_6284bd09cda5bb788913be974d7a100f -->

<!-- START_edd2ddfb19debc67e508988be2fa4c4a -->
## Delete the reminder.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/journal/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/journal/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE journal/{entry}`


<!-- END_edd2ddfb19debc67e508988be2fa4c4a -->

<!-- START_62c09084921155416dc5e292b293a549 -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings`


<!-- END_62c09084921155416dc5e292b293a549 -->

<!-- START_1335c1c695c887e3cf909d8cda630245 -->
## Delete user account.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/delete" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/delete"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/delete`


<!-- END_1335c1c695c887e3cf909d8cda630245 -->

<!-- START_cc6cf1a615d02427b9007028b9a959c7 -->
## Reset user account.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/reset" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/reset"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/reset`


<!-- END_cc6cf1a615d02427b9007028b9a959c7 -->

<!-- START_4ff3d101f373ad421f269e29bb3b92b0 -->
## Save user settings.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/save" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/save"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/save`


<!-- END_4ff3d101f373ad421f269e29bb3b92b0 -->

<!-- START_53ec3415c5f9d84861054e1438909384 -->
## Display the personalization page.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/personalization" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/personalization`


<!-- END_53ec3415c5f9d84861054e1438909384 -->

<!-- START_39ccc5c35a23a28f98bc5d06a3a704c0 -->
## Get all the contact field types.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/personalization/contactfieldtypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/contactfieldtypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/personalization/contactfieldtypes`


<!-- END_39ccc5c35a23a28f98bc5d06a3a704c0 -->

<!-- START_84411ec62ce215bbb467bb2768a8dec7 -->
## Store a newly created resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/personalization/contactfieldtypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/contactfieldtypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/personalization/contactfieldtypes`


<!-- END_84411ec62ce215bbb467bb2768a8dec7 -->

<!-- START_d11b79ece591cf771f6ae434f47b02a0 -->
## Edit a newly created resource in storage.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/settings/personalization/contactfieldtypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/contactfieldtypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT settings/personalization/contactfieldtypes/{contactFieldType}`


<!-- END_d11b79ece591cf771f6ae434f47b02a0 -->

<!-- START_aa0fa44e3a7ac9d5999132ef6ed7939b -->
## Destroy the contact field type.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/personalization/contactfieldtypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/contactfieldtypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/personalization/contactfieldtypes/{contactFieldType}`


<!-- END_aa0fa44e3a7ac9d5999132ef6ed7939b -->

<!-- START_824b27667f41b99e169e08eb611e12ec -->
## Get all the gender types.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/personalization/genders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/genders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/personalization/genders`


<!-- END_824b27667f41b99e169e08eb611e12ec -->

<!-- START_6f7318fa89107c3edd311687cf0c35bc -->
## Store the gender.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/personalization/genders" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/genders"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/personalization/genders`


<!-- END_6f7318fa89107c3edd311687cf0c35bc -->

<!-- START_3df447424cfef2c9e9ac9a6121db7254 -->
## Update the given gender.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/settings/personalization/genders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/genders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT settings/personalization/genders/{gender}`

`PATCH settings/personalization/genders/{gender}`


<!-- END_3df447424cfef2c9e9ac9a6121db7254 -->

<!-- START_a5e8baf97a71ea44a7a15f07b7d41d80 -->
## Destroy a gender type.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/personalization/genders/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/genders/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/personalization/genders/{gender}`


<!-- END_a5e8baf97a71ea44a7a15f07b7d41d80 -->

<!-- START_418ba8a7dbae4ce865e8f375fd5502db -->
## Destroy a gender type.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/personalization/genders/1/replaceby/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/genders/1/replaceby/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/personalization/genders/{gender}/replaceby/{genderToReplaceWith}`


<!-- END_418ba8a7dbae4ce865e8f375fd5502db -->

<!-- START_58eff5dca5d7f63bdee95529c49f2698 -->
## Get all the gender sex types.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/personalization/genderTypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/genderTypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/personalization/genderTypes`


<!-- END_58eff5dca5d7f63bdee95529c49f2698 -->

<!-- START_ce9bed79fb67e1bd713b5e8afa2c667a -->
## Update the given gender to the default gender.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/settings/personalization/genders/default/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/genders/default/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT settings/personalization/genders/default/{gender}`


<!-- END_ce9bed79fb67e1bd713b5e8afa2c667a -->

<!-- START_395423dcd11f33f95980305d8877d029 -->
## Get all the reminder rules.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/personalization/reminderrules" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/reminderrules"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/personalization/reminderrules`


<!-- END_395423dcd11f33f95980305d8877d029 -->

<!-- START_2e70d1370c11d0d3233d8f1bf356e517 -->
## settings/personalization/reminderrules/{reminderRule}
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/personalization/reminderrules/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/reminderrules/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/personalization/reminderrules/{reminderRule}`


<!-- END_2e70d1370c11d0d3233d8f1bf356e517 -->

<!-- START_750d5b7139e38589a161dd3462117555 -->
## Get all the reminder rules.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/personalization/modules" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/modules"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/personalization/modules`


<!-- END_750d5b7139e38589a161dd3462117555 -->

<!-- START_9eb4d206293611468c993225fb5d770b -->
## settings/personalization/modules/{module}
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/personalization/modules/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/modules/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/personalization/modules/{module}`


<!-- END_9eb4d206293611468c993225fb5d770b -->

<!-- START_0b0f57d10a4ec11b52f357a7b505b44c -->
## Get all the activity type categories.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/personalization/activitytypecategories" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/activitytypecategories"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/personalization/activitytypecategories`


<!-- END_0b0f57d10a4ec11b52f357a7b505b44c -->

<!-- START_ffe29e0c6f48a7875f78174ee9430f41 -->
## Store an activity type category.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/personalization/activitytypecategories" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/activitytypecategories"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/personalization/activitytypecategories`


<!-- END_ffe29e0c6f48a7875f78174ee9430f41 -->

<!-- START_94fe5e874e818aa6223fe79f3d37a331 -->
## Update an activity type category.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/settings/personalization/activitytypecategories/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/activitytypecategories/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT settings/personalization/activitytypecategories/{activitytypecategory}`

`PATCH settings/personalization/activitytypecategories/{activitytypecategory}`


<!-- END_94fe5e874e818aa6223fe79f3d37a331 -->

<!-- START_65a6f57944515b70648149e66cfe3980 -->
## Delete the activity type category.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/personalization/activitytypecategories/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/activitytypecategories/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/personalization/activitytypecategories/{activitytypecategory}`


<!-- END_65a6f57944515b70648149e66cfe3980 -->

<!-- START_395f01b1f826a6044a77962ad494acf5 -->
## Store an activity type category.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/personalization/activitytypes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/activitytypes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/personalization/activitytypes`


<!-- END_395f01b1f826a6044a77962ad494acf5 -->

<!-- START_07f87e720dbc7ff98de1beb0c30d716b -->
## Update an activity type.

> Example request:

```bash
curl -X PUT \
    "https://monicalocal.test/settings/personalization/activitytypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/activitytypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "PUT",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`PUT settings/personalization/activitytypes/{activitytype}`

`PATCH settings/personalization/activitytypes/{activitytype}`


<!-- END_07f87e720dbc7ff98de1beb0c30d716b -->

<!-- START_0d578648edf48740f3c3f881acb984d5 -->
## Delete the activity type.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/personalization/activitytypes/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/personalization/activitytypes/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/personalization/activitytypes/{activitytype}`


<!-- END_0d578648edf48740f3c3f881acb984d5 -->

<!-- START_b3dac5d33c823c83a9108591d6696c4b -->
## Display the export view.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/export" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/export"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/export`


<!-- END_b3dac5d33c823c83a9108591d6696c4b -->

<!-- START_ea8a4d606d5606c20e61ca275a3d3077 -->
## Exports the data of the account in SQL format.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/exportToSql" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/exportToSql"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/exportToSql`


<!-- END_ea8a4d606d5606c20e61ca275a3d3077 -->

<!-- START_39f9e8b99a9fe02129e1cadcd72d8877 -->
## Display the import view.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/import" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/import"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/import`


<!-- END_39f9e8b99a9fe02129e1cadcd72d8877 -->

<!-- START_dab4569a3eaf1b3c6f4db0ac8d9bf7fb -->
## Display the import report view.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/import/report/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/import/report/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/import/report/{importjobid}`


<!-- END_dab4569a3eaf1b3c6f4db0ac8d9bf7fb -->

<!-- START_f924721e88c835388b532c6a5fc419ba -->
## Display the Import people&#039;s view.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/import/upload" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/import/upload"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/import/upload`


<!-- END_f924721e88c835388b532c6a5fc419ba -->

<!-- START_79f43a045708f78ccdfa20001eb34c47 -->
## settings/import/storeImport
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/import/storeImport" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/import/storeImport"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/import/storeImport`


<!-- END_79f43a045708f78ccdfa20001eb34c47 -->

<!-- START_8693024cfbcddcd0628395709dd78e79 -->
## Display the users view.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/users" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/users"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/users`


<!-- END_8693024cfbcddcd0628395709dd78e79 -->

<!-- START_65f88e9acd67d1ea6d5d76d5a68194f9 -->
## Show the form for creating a new resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/users/create" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/users/create"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/users/create`


<!-- END_65f88e9acd67d1ea6d5d76d5a68194f9 -->

<!-- START_285b2d9e9e98079a58ac078bfad693fd -->
## Store a newly created resource in storage.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/users" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/users"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/users`


<!-- END_285b2d9e9e98079a58ac078bfad693fd -->

<!-- START_b05b5cbedd3f9110ae4787fa3e72b6e5 -->
## Delete additional user account.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/users/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/users/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/users/{user}`


<!-- END_b05b5cbedd3f9110ae4787fa3e72b6e5 -->

<!-- START_de4c81673ee645681b8c9ca7771ed135 -->
## Remove the specified resource from storage.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/users/invitations/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/users/invitations/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/users/invitations/{invitation}`


<!-- END_de4c81673ee645681b8c9ca7771ed135 -->

<!-- START_fa2eb1e423c7cfcc621bbfc9c51c233b -->
## Get all the information about the account in terms of storage.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/storage" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/storage"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/storage`


<!-- END_fa2eb1e423c7cfcc621bbfc9c51c233b -->

<!-- START_bd215f8a188678689fcaf321763bcd19 -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/subscriptions" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/subscriptions`


<!-- END_bd215f8a188678689fcaf321763bcd19 -->

<!-- START_d688c9bedcd9619793aa05fbaabd33bc -->
## Display the upgrade view page.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/subscriptions/upgrade" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/upgrade"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/subscriptions/upgrade`


<!-- END_d688c9bedcd9619793aa05fbaabd33bc -->

<!-- START_9d27fb59dbaa6cfecd0c834cbbe68a89 -->
## Display the upgrade success page.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/subscriptions/upgrade/success" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/upgrade/success"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/subscriptions/upgrade/success`


<!-- END_9d27fb59dbaa6cfecd0c834cbbe68a89 -->

<!-- START_6dffc7ce081e8a1f45fb83ed0d94340e -->
## Display the confirm view page.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/subscriptions/confirmPayment/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/confirmPayment/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/subscriptions/confirmPayment/{id}`


<!-- END_6dffc7ce081e8a1f45fb83ed0d94340e -->

<!-- START_410fa356736f406500e4d482f4413a24 -->
## Process the upgrade payment.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/subscriptions/processPayment" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/processPayment"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/subscriptions/processPayment`


<!-- END_410fa356736f406500e4d482f4413a24 -->

<!-- START_027e641672803739de78c040fd3a753f -->
## Download the invoice as PDF.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/subscriptions/invoice/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/invoice/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/subscriptions/invoice/{invoice}`


<!-- END_027e641672803739de78c040fd3a753f -->

<!-- START_cc89761a5db27f666d1e5454d12192c1 -->
## Display the downgrade view page.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/subscriptions/downgrade" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/downgrade"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/subscriptions/downgrade`


<!-- END_cc89761a5db27f666d1e5454d12192c1 -->

<!-- START_7d50227c53ffbb38dae11eaef0130311 -->
## Process the downgrade process.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/subscriptions/downgrade" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/downgrade"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/subscriptions/downgrade`


<!-- END_7d50227c53ffbb38dae11eaef0130311 -->

<!-- START_cb8e563612eb7e30fa739c3913d85815 -->
## Display the downgrade success page.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/subscriptions/downgrade/success" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/downgrade/success"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/subscriptions/downgrade/success`


<!-- END_cb8e563612eb7e30fa739c3913d85815 -->

<!-- START_22d3aa7dacb5b79db2e2503195ff5010 -->
## Download the invoice as PDF.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/subscriptions/forceCompletePaymentOnTesting" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/subscriptions/forceCompletePaymentOnTesting"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/subscriptions/forceCompletePaymentOnTesting`


<!-- END_22d3aa7dacb5b79db2e2503195ff5010 -->

<!-- START_f6affe8037ec1f1ad09ae48d10780353 -->
## Display the list of tags for this account.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/tags" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/tags"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/tags`


<!-- END_f6affe8037ec1f1ad09ae48d10780353 -->

<!-- START_f021f3f51b714cb4a12007b19f5d9d32 -->
## Show the form for creating a new resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/tags/add" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/tags/add"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/tags/add`


<!-- END_f021f3f51b714cb4a12007b19f5d9d32 -->

<!-- START_33a73c79315250f0e66854401446e523 -->
## Destroy the tag.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/tags/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/tags/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/tags/{tag}`


<!-- END_33a73c79315250f0e66854401446e523 -->

<!-- START_0d73817186b07536b174c1e8a016bbb0 -->
## settings/api
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/api" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/api"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/api`


<!-- END_0d73817186b07536b174c1e8a016bbb0 -->

<!-- START_8f974e16578e545d3fe3ac078aaab892 -->
## settings/dav
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/dav" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/dav"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/dav`


<!-- END_8f974e16578e545d3fe3ac078aaab892 -->

<!-- START_44058229ad1aece4eab0aacabccd6198 -->
## Update the default view when viewing a contact.

The default view can be either the life events feed or the general data
about the contact (notes, reminders, ...).
Possible values: life-events | notes.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/updateDefaultProfileView" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/updateDefaultProfileView"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/updateDefaultProfileView`


<!-- END_44058229ad1aece4eab0aacabccd6198 -->

<!-- START_e75c452f2c2e7ca4e130b1f65fa39152 -->
## settings/security
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/security" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/security`


<!-- END_e75c452f2c2e7ca4e130b1f65fa39152 -->

<!-- START_61f17398dfdb40fd6b2d675298511b31 -->
## Change user password.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/security/passwordChange" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/passwordChange"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/security/passwordChange`


<!-- END_61f17398dfdb40fd6b2d675298511b31 -->

<!-- START_da7f32469da83ecee75469b9fc2b8617 -->
## settings/security/2fa-enable
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/security/2fa-enable" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/2fa-enable"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/security/2fa-enable`


<!-- END_da7f32469da83ecee75469b9fc2b8617 -->

<!-- START_0ae7c6f2dc5a757e28ba922add0963e7 -->
## settings/security/2fa-enable
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/security/2fa-enable" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/2fa-enable"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/security/2fa-enable`


<!-- END_0ae7c6f2dc5a757e28ba922add0963e7 -->

<!-- START_244ea495a6498f78590d0d94e10aed9a -->
## settings/security/2fa-disable
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/security/2fa-disable" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/2fa-disable"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/security/2fa-disable`


<!-- END_244ea495a6498f78590d0d94e10aed9a -->

<!-- START_8907d09bf023d7a7d933803dc8d4f153 -->
## settings/security/2fa-disable
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/security/2fa-disable" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/2fa-disable"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/security/2fa-disable`


<!-- END_8907d09bf023d7a7d933803dc8d4f153 -->

<!-- START_0fbe96e876b7d617ae88c989fff5d0b6 -->
## settings/security/u2f/register
> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/security/u2f/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/u2f/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/security/u2f/register`


<!-- END_0fbe96e876b7d617ae88c989fff5d0b6 -->

<!-- START_51396e3acc9fb6621adbbebc4b1114c1 -->
## settings/security/u2f/register
> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/security/u2f/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/u2f/register"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/security/u2f/register`


<!-- END_51396e3acc9fb6621adbbebc4b1114c1 -->

<!-- START_4f4fad8adb91eb0da2017309475f71aa -->
## Remove an existing security key.

> Example request:

```bash
curl -X DELETE \
    "https://monicalocal.test/settings/security/u2f/remove/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/u2f/remove/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`DELETE settings/security/u2f/remove/{u2fKeyId}`


<!-- END_4f4fad8adb91eb0da2017309475f71aa -->

<!-- START_75361e0c5b0b7aeb48ff2853330e3329 -->
## Generate recovery codes.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/security/generate-recovery-codes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/generate-recovery-codes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/security/generate-recovery-codes`


<!-- END_75361e0c5b0b7aeb48ff2853330e3329 -->

<!-- START_9c6a0ea92ed81a1519f235b88d2f26ff -->
## Get list of recovery codes.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/security/recovery-codes" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/security/recovery-codes"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/security/recovery-codes`


<!-- END_9c6a0ea92ed81a1519f235b88d2f26ff -->

<!-- START_9bb93d7ddf3f622bc99d0f644c88f537 -->
## Display a log in form for oauth accessToken.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/oauth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/login"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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

```bash
curl -X POST \
    "https://monicalocal.test/oauth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/login"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST oauth/login`


<!-- END_c599020b8dabac913a5a326a94d61a23 -->

<!-- START_0cfcdcf544073bff7de7ad271cdaa95f -->
## Log in a user and returns an accessToken.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/oauth/verified" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/verified"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST oauth/verified`


<!-- END_0cfcdcf544073bff7de7ad271cdaa95f -->

<!-- START_42be8725f24757777bc1804a0042a2f1 -->
## Redirect the user after 2fa form has been submitted.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/oauth/validate2fa" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/oauth/validate2fa"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST oauth/validate2fa`


<!-- END_42be8725f24757777bc1804a0042a2f1 -->

<!-- START_4edc224cb85f440d26aaa10abed5d129 -->
## Show the application&#039;s login form.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/emailchange1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/emailchange1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


> Example response (302):

```json
null
```

### HTTP Request
`GET settings/emailchange1`


<!-- END_4edc224cb85f440d26aaa10abed5d129 -->

<!-- START_0fc310329c7516a08b878f0e7c4eaf97 -->
## Handle a login request to the application.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/emailchange1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/emailchange1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/emailchange1`


<!-- END_0fc310329c7516a08b878f0e7c4eaf97 -->

<!-- START_28843a7662edd80d4a5f2f2ccffcb7ee -->
## Display a listing of the resource.

> Example request:

```bash
curl -X GET \
    -G "https://monicalocal.test/settings/emailchange2" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/emailchange2"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
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
`GET settings/emailchange2`


<!-- END_28843a7662edd80d4a5f2f2ccffcb7ee -->

<!-- START_999fc5bc12255f13f9b30a040e174fe9 -->
## Change user email.

> Example request:

```bash
curl -X POST \
    "https://monicalocal.test/settings/emailchange2" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "https://monicalocal.test/settings/emailchange2"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```



### HTTP Request
`POST settings/emailchange2`


<!-- END_999fc5bc12255f13f9b30a040e174fe9 -->



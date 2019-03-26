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
[Get Postman Collection](http://localhost/apidoc/collection.json)

<!-- END_INFO -->

#Activities
<!-- START_7651fa39308e031728c794ef2c6be240 -->
## Get the list of activities.

> Example request:

```bash
curl -X GET -G "http://localhost/api/activities" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activities");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/activities?page=1",
        "last": "http:\/\/localhost\/api\/activities?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/activities",
        "per_page": 15,
        "to": null,
        "total": 0,
        "statistics": []
    }
}
```

### HTTP Request
`GET api/activities`


<!-- END_7651fa39308e031728c794ef2c6be240 -->

<!-- START_ae20a6beba7f4129ed09833ac1728b99 -->
## Store the activity.

> Example request:

```bash
curl -X POST "http://localhost/api/activities" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activities");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/activities/{activity}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activities/{activity}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/activities/{activity}`


<!-- END_c80b1e4f2293abbba46e63e089e9da48 -->

<!-- START_12187850e57bf40c9bf553d5e33c3575 -->
## Update the activity.

> Example request:

```bash
curl -X PUT "http://localhost/api/activities/{activity}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activities/{activity}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/activities/{activity}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activities/{activity}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/activities" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/activities");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/activitytypecategories" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypecategories");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
            "id": 248,
            "object": "activityTypeCategory",
            "name": "Simple activities",
            "account": {
                "id": 78
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 249,
            "object": "activityTypeCategory",
            "name": "Sport",
            "account": {
                "id": 78
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 250,
            "object": "activityTypeCategory",
            "name": "Food",
            "account": {
                "id": 78
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 251,
            "object": "activityTypeCategory",
            "name": "Cultural activities",
            "account": {
                "id": 78
            },
            "created_at": null,
            "updated_at": null
        }
    ],
    "links": {
        "first": "http:\/\/localhost\/api\/activitytypecategories?page=1",
        "last": "http:\/\/localhost\/api\/activitytypecategories?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/activitytypecategories",
        "per_page": 15,
        "to": 4,
        "total": 4
    }
}
```

### HTTP Request
`GET api/activitytypecategories`


<!-- END_75269b2ab10144fc81137f3884fe1c18 -->

<!-- START_c81ecd528ab92b471b75287391488c15 -->
## Store the activity type category.

> Example request:

```bash
curl -X POST "http://localhost/api/activitytypecategories" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypecategories");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/activitytypecategories/{activitytypecategory}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypecategories/{activitytypecategory}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/activitytypecategories/{activitytypecategory}`


<!-- END_56b9983d0d8420b1911e9344ae04e362 -->

<!-- START_f81458cc224f02f65e6d64e36c01a0c9 -->
## Update the activity type category.

> Example request:

```bash
curl -X PUT "http://localhost/api/activitytypecategories/{activitytypecategory}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypecategories/{activitytypecategory}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/activitytypecategories/{activitytypecategory}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypecategories/{activitytypecategory}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/activitytypes" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypes");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
            "id": 771,
            "object": "activityType",
            "name": "just hung out",
            "location_type": null,
            "activity_type_category": {
                "id": 240,
                "object": "activityTypeCategory",
                "name": "Simple activities",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 772,
            "object": "activityType",
            "name": "watched a movie at home",
            "location_type": null,
            "activity_type_category": {
                "id": 240,
                "object": "activityTypeCategory",
                "name": "Simple activities",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 773,
            "object": "activityType",
            "name": "just talked at home",
            "location_type": null,
            "activity_type_category": {
                "id": 240,
                "object": "activityTypeCategory",
                "name": "Simple activities",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 774,
            "object": "activityType",
            "name": "did sport together",
            "location_type": null,
            "activity_type_category": {
                "id": 241,
                "object": "activityTypeCategory",
                "name": "Sport",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 775,
            "object": "activityType",
            "name": "ate at their place",
            "location_type": null,
            "activity_type_category": {
                "id": 242,
                "object": "activityTypeCategory",
                "name": "Food",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 776,
            "object": "activityType",
            "name": "went to a bar",
            "location_type": null,
            "activity_type_category": {
                "id": 242,
                "object": "activityTypeCategory",
                "name": "Food",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 777,
            "object": "activityType",
            "name": "ate at home",
            "location_type": null,
            "activity_type_category": {
                "id": 242,
                "object": "activityTypeCategory",
                "name": "Food",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 778,
            "object": "activityType",
            "name": "picknicked",
            "location_type": null,
            "activity_type_category": {
                "id": 242,
                "object": "activityTypeCategory",
                "name": "Food",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 779,
            "object": "activityType",
            "name": "ate at a restaurant",
            "location_type": null,
            "activity_type_category": {
                "id": 242,
                "object": "activityTypeCategory",
                "name": "Food",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 780,
            "object": "activityType",
            "name": "went to the theater",
            "location_type": null,
            "activity_type_category": {
                "id": 243,
                "object": "activityTypeCategory",
                "name": "Cultural activities",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 781,
            "object": "activityType",
            "name": "went to a concert",
            "location_type": null,
            "activity_type_category": {
                "id": 243,
                "object": "activityTypeCategory",
                "name": "Cultural activities",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 782,
            "object": "activityType",
            "name": "went to a play",
            "location_type": null,
            "activity_type_category": {
                "id": 243,
                "object": "activityTypeCategory",
                "name": "Cultural activities",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 783,
            "object": "activityType",
            "name": "went to the museum",
            "location_type": null,
            "activity_type_category": {
                "id": 243,
                "object": "activityTypeCategory",
                "name": "Cultural activities",
                "account": {
                    "id": 76
                },
                "created_at": null,
                "updated_at": null
            },
            "account": {
                "id": 76
            },
            "created_at": null,
            "updated_at": null
        }
    ],
    "links": {
        "first": "http:\/\/localhost\/api\/activitytypes?page=1",
        "last": "http:\/\/localhost\/api\/activitytypes?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/activitytypes",
        "per_page": 15,
        "to": 13,
        "total": 13
    }
}
```

### HTTP Request
`GET api/activitytypes`


<!-- END_50b58c22e6424f9f6e44c35e980cbfca -->

<!-- START_c6fbb838fcd22e7e52fb35a33b89d3b8 -->
## Store the activity type.

> Example request:

```bash
curl -X POST "http://localhost/api/activitytypes" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypes");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/activitytypes/{activitytype}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypes/{activitytype}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/activitytypes/{activitytype}`


<!-- END_f8904387316b8807b00c41f552029e76 -->

<!-- START_a9d6e966358fbe66b79e92f03046ffc8 -->
## Update the activity type.

> Example request:

```bash
curl -X PUT "http://localhost/api/activitytypes/{activitytype}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypes/{activitytype}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/activitytypes/{activitytype}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/activitytypes/{activitytype}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/addresses" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/addresses");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/addresses?page=1",
        "last": "http:\/\/localhost\/api\/addresses?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/addresses",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/addresses`


<!-- END_f62d434079dff3acd53aa774d160d2ad -->

<!-- START_c8fad65a796e6206c26cb584c46221b7 -->
## Store the address.

> Example request:

```bash
curl -X POST "http://localhost/api/addresses" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/addresses");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/addresses/{address}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/addresses/{address}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/addresses/{address}`


<!-- END_25f4303d28e06d127578df97937cdb67 -->

<!-- START_8f97ba08be391bb75680a4b5a24c9f6d -->
## Update the address.

> Example request:

```bash
curl -X PUT "http://localhost/api/addresses/{address}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/addresses/{address}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/addresses/{address}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/addresses/{address}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/addresses" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/addresses");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/calls" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/calls");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/calls?page=1",
        "last": "http:\/\/localhost\/api\/calls?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/calls",
        "per_page": 15,
        "to": null,
        "total": 0,
        "statistics": []
    }
}
```

### HTTP Request
`GET api/calls`


<!-- END_f39b2e3781ab5d7ea23125345c5be75e -->

<!-- START_4ab385e6babe171ebd61d88e554311bf -->
## Store the call.

> Example request:

```bash
curl -X POST "http://localhost/api/calls" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/calls");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/calls/{call}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/calls/{call}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/calls/{call}`


<!-- END_69b2082c217cd5a4fd92ca1b57da1c5a -->

<!-- START_2f310581427b19466445b4ae46eaa8f2 -->
## Update a call.

> Example request:

```bash
curl -X PUT "http://localhost/api/calls/{call}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/calls/{call}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/calls/{call}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/calls/{call}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/calls" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/calls");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/companies" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/companies");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/companies?page=1",
        "last": "http:\/\/localhost\/api\/companies?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/companies",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/companies`


<!-- END_83764a2de1a941a0a3cbae52bba9776e -->

<!-- START_a242a34f0abd359a9196226970606774 -->
## Store the company.

> Example request:

```bash
curl -X POST "http://localhost/api/companies" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/companies");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/companies/{company}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/companies/{company}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/companies/{company}`


<!-- END_b4015228dd0e0c0b6a959ebaf0865a05 -->

<!-- START_1e6a34851b0689db52677b43727419b5 -->
## Update a company.

> Example request:

```bash
curl -X PUT "http://localhost/api/companies/{company}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/companies/{company}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/companies/{company}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/companies/{company}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contactfieldtypes" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfieldtypes");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
            "id": 500,
            "object": "contactfieldtype",
            "name": "Email",
            "fontawesome_icon": "fa fa-envelope-open-o",
            "protocol": "mailto:",
            "delible": false,
            "type": "email",
            "account": {
                "id": 89
            },
            "created_at": "2019-03-26T22:35:52Z",
            "updated_at": "2019-03-26T22:35:52Z"
        },
        {
            "id": 501,
            "object": "contactfieldtype",
            "name": "Phone",
            "fontawesome_icon": "fa fa-volume-control-phone",
            "protocol": "tel:",
            "delible": false,
            "type": "phone",
            "account": {
                "id": 89
            },
            "created_at": "2019-03-26T22:35:52Z",
            "updated_at": "2019-03-26T22:35:52Z"
        },
        {
            "id": 502,
            "object": "contactfieldtype",
            "name": "Facebook",
            "fontawesome_icon": "fa fa-facebook-official",
            "protocol": null,
            "delible": true,
            "type": null,
            "account": {
                "id": 89
            },
            "created_at": "2019-03-26T22:35:52Z",
            "updated_at": "2019-03-26T22:35:52Z"
        },
        {
            "id": 503,
            "object": "contactfieldtype",
            "name": "Twitter",
            "fontawesome_icon": "fa fa-twitter-square",
            "protocol": null,
            "delible": true,
            "type": null,
            "account": {
                "id": 89
            },
            "created_at": "2019-03-26T22:35:52Z",
            "updated_at": "2019-03-26T22:35:52Z"
        },
        {
            "id": 504,
            "object": "contactfieldtype",
            "name": "Whatsapp",
            "fontawesome_icon": "fa fa-whatsapp",
            "protocol": null,
            "delible": true,
            "type": null,
            "account": {
                "id": 89
            },
            "created_at": "2019-03-26T22:35:52Z",
            "updated_at": "2019-03-26T22:35:52Z"
        },
        {
            "id": 505,
            "object": "contactfieldtype",
            "name": "Telegram",
            "fontawesome_icon": "fa fa-telegram",
            "protocol": "telegram:",
            "delible": true,
            "type": null,
            "account": {
                "id": 89
            },
            "created_at": "2019-03-26T22:35:52Z",
            "updated_at": "2019-03-26T22:35:52Z"
        },
        {
            "id": 506,
            "object": "contactfieldtype",
            "name": "LinkedIn",
            "fontawesome_icon": "fa fa-linkedin-square",
            "protocol": null,
            "delible": true,
            "type": null,
            "account": {
                "id": 89
            },
            "created_at": "2019-03-26T22:35:52Z",
            "updated_at": "2019-03-26T22:35:52Z"
        }
    ],
    "links": {
        "first": "http:\/\/localhost\/api\/contactfieldtypes?page=1",
        "last": "http:\/\/localhost\/api\/contactfieldtypes?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/contactfieldtypes",
        "per_page": 15,
        "to": 7,
        "total": 7
    }
}
```

### HTTP Request
`GET api/contactfieldtypes`


<!-- END_20e1ef2faa9e28220eeed8eb18242993 -->

<!-- START_6bafa4d9a8d87581a2f31388258d66f8 -->
## Store the contactfieldtype.

> Example request:

```bash
curl -X POST "http://localhost/api/contactfieldtypes" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfieldtypes");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contactfieldtypes/{contactfieldtype}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfieldtypes/{contactfieldtype}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/contactfieldtypes/{contactfieldtype}`


<!-- END_f315c3eb9b55add217ddcc5333e1d666 -->

<!-- START_ce34dc76ea233436e1958b2b806b6946 -->
## Update the contact field type.

> Example request:

```bash
curl -X PUT "http://localhost/api/contactfieldtypes/{contactfieldtype}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfieldtypes/{contactfieldtype}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/contactfieldtypes/{contactfieldtype}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfieldtypes/{contactfieldtype}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X POST "http://localhost/api/contactfields" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfields");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contactfields/{contactfield}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfields/{contactfield}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/contactfields/{contactfield}`


<!-- END_4ab8200795745727f1e7e1f6ceb9a087 -->

<!-- START_64c738c591a84277b1cb115f5fa35246 -->
## Update the contactField.

> Example request:

```bash
curl -X PUT "http://localhost/api/contactfields/{contactfield}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfields/{contactfield}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/contactfields/{contactfield}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contactfields/{contactfield}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/contactfields" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/contactfields");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/contacts" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/contacts?page=1",
        "last": "http:\/\/localhost\/api\/contacts?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/contacts",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/contacts`


<!-- END_543b0b80e8dc51d2d3ad7e2a327eed26 -->

<!-- START_e1625404aaf762aa591c10b259222b07 -->
## Store the contact.

> Example request:

```bash
curl -X POST "http://localhost/api/contacts" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/contacts/{contact}`


<!-- END_a44483465b9aa8cdb47a73e922b4dd91 -->

<!-- START_6855fa612757e2be32b2250d88260a29 -->
## Update the contact.

> Example request:

```bash
curl -X PUT "http://localhost/api/contacts/{contact}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/contacts/{contact}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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

#Conversations
<!-- START_50f5969ffa4376ab4d09a74616534468 -->
## Get the list of conversations.

> Example request:

```bash
curl -X GET -G "http://localhost/api/conversations" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/conversations");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/conversations?page=1",
        "last": "http:\/\/localhost\/api\/conversations?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/conversations",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/conversations`


<!-- END_50f5969ffa4376ab4d09a74616534468 -->

<!-- START_1d6ac3c69bc5f2271b33806815418dc6 -->
## Store the conversation.

> Example request:

```bash
curl -X POST "http://localhost/api/conversations" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/conversations");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/conversations/{conversation}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/conversations/{conversation}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/conversations/{conversation}`


<!-- END_91ffe2990f59ed18f147da555d60af64 -->

<!-- START_585f955d45f3290fce1897f0936ddb38 -->
## Update the conversation.

> Example request:

```bash
curl -X PUT "http://localhost/api/conversations/{conversation}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/conversations/{conversation}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/conversations/{conversation}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/conversations/{conversation}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/conversations" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/conversations");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/debts" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/debts");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/debts?page=1",
        "last": "http:\/\/localhost\/api\/debts?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/debts",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/debts`


<!-- END_bb01ae3621e7fa60ffe6550de5f73945 -->

<!-- START_2f59f5ecb2f53bce8af0abf3f52908dc -->
## Store the debt.

> Example request:

```bash
curl -X POST "http://localhost/api/debts" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/debts");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/debts/{debt}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/debts/{debt}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/debts/{debt}`


<!-- END_770e01d3360bdd2ada85b87bcc0cd9a0 -->

<!-- START_c7b568dee9739fb9a8c07be209586334 -->
## Update the debt.

> Example request:

```bash
curl -X PUT "http://localhost/api/debts/{debt}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/debts/{debt}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/debts/{debt}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/debts/{debt}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/debts" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/debts");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/documents" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/documents");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/documents?page=1",
        "last": "http:\/\/localhost\/api\/documents?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/documents",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/documents`


<!-- END_2db491b98c54e01029e35dcce36f2edf -->

<!-- START_73cdbcb5aa2de3c17d903aceef407d78 -->
## Get the detail of a given document.

> Example request:

```bash
curl -X GET -G "http://localhost/api/documents/{document}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/documents/{document}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/documents/{document}`


<!-- END_73cdbcb5aa2de3c17d903aceef407d78 -->

<!-- START_fd15fa6846d168278d7085f22a2cd1e3 -->
## Get the list of documents for a specific contact.

> Example request:

```bash
curl -X GET -G "http://localhost/api/contacts/{contact}/documents" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/documents");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/genders" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/genders");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
            "id": 45,
            "object": "gender",
            "name": "Man",
            "account": {
                "id": 30
            },
            "created_at": "2019-03-26T22:35:16Z",
            "updated_at": "2019-03-26T22:35:16Z"
        },
        {
            "id": 46,
            "object": "gender",
            "name": "Woman",
            "account": {
                "id": 30
            },
            "created_at": "2019-03-26T22:35:16Z",
            "updated_at": "2019-03-26T22:35:16Z"
        },
        {
            "id": 47,
            "object": "gender",
            "name": "Rather not say",
            "account": {
                "id": 30
            },
            "created_at": "2019-03-26T22:35:16Z",
            "updated_at": "2019-03-26T22:35:16Z"
        }
    ],
    "links": {
        "first": "http:\/\/localhost\/api\/genders?page=1",
        "last": "http:\/\/localhost\/api\/genders?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/genders",
        "per_page": 15,
        "to": 3,
        "total": 3
    }
}
```

### HTTP Request
`GET api/genders`


<!-- END_9c8ad14de9a6ed2b154a3e2a836ff686 -->

<!-- START_c1627d540a1e4e851a229d8af7239265 -->
## Store the gender.

> Example request:

```bash
curl -X POST "http://localhost/api/genders" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/genders");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/genders/{gender}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/genders/{gender}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/genders/{gender}`


<!-- END_68717289f71f412d9dc1badb0cf9e322 -->

<!-- START_78403840ae21164785a057e9a44e7e20 -->
## Update a gender.

> Example request:

```bash
curl -X PUT "http://localhost/api/genders/{gender}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/genders/{gender}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/genders/{gender}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/genders/{gender}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/gifts" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/gifts");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/gifts?page=1",
        "last": "http:\/\/localhost\/api\/gifts?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/gifts",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/gifts`


<!-- END_965e17f1877da4447d9227c61e48b1d1 -->

<!-- START_72230615152e8879fdc448e920d68de5 -->
## Store the gift.

> Example request:

```bash
curl -X POST "http://localhost/api/gifts" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/gifts");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/gifts/{gift}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/gifts/{gift}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/gifts/{gift}`


<!-- END_5edda6afb6fbe6807a22105f5d9792c6 -->

<!-- START_d772ddb617aeb9a0c32078529acd3da0 -->
## Update the gift.

> Example request:

```bash
curl -X PUT "http://localhost/api/gifts/{gift}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/gifts/{gift}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/gifts/{gift}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/gifts/{gift}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/gifts" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/gifts");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/journal" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/journal");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/journal?page=1",
        "last": "http:\/\/localhost\/api\/journal?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/journal",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/journal`


<!-- END_0d9b4d8c77f9fa7803a08d3a82f5bc76 -->

<!-- START_913adbebc140a76c892c0f9483949e11 -->
## Store the call.

> Example request:

```bash
curl -X POST "http://localhost/api/journal" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/journal");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/journal/{journal}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/journal/{journal}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/journal/{journal}`


<!-- END_227f3459c6c02a4eaa16d1b569b5b877 -->

<!-- START_0c3350ec076a9df3b7a6ec5c80660437 -->
## Update the note.

> Example request:

```bash
curl -X PUT "http://localhost/api/journal/{journal}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/journal/{journal}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/journal/{journal}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/journal/{journal}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/lifeevents" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/lifeevents");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/lifeevents?page=1",
        "last": "http:\/\/localhost\/api\/lifeevents?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/lifeevents",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/lifeevents`


<!-- END_25bbc444b878d0893b40f9f237062882 -->

<!-- START_b0dcd0f39cd034964f0c43f5790d24dd -->
## Store the life event.

> Example request:

```bash
curl -X POST "http://localhost/api/lifeevents" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/lifeevents");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/lifeevents/{lifeevent}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/lifeevents/{lifeevent}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/lifeevents/{lifeevent}`


<!-- END_a917fecbccdb3d7c45379345f36f33c7 -->

<!-- START_66abc6c555a8b77d16241c511daa557a -->
## Update the life event.

> Example request:

```bash
curl -X PUT "http://localhost/api/lifeevents/{lifeevent}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/lifeevents/{lifeevent}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/lifeevents/{lifeevent}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/lifeevents/{lifeevent}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X POST "http://localhost/api/conversations/{conversation}/messages" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/conversations/{conversation}/messages");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X PUT "http://localhost/api/conversations/{conversation}/messages/{message}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/conversations/{conversation}/messages/{message}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/conversations/{conversation}/messages/{message}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/conversations/{conversation}/messages/{message}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/statistics" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/statistics");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/statistics`


<!-- END_8ff05e238f60fee26c8b9f40731fa11f -->

<!-- START_28735cbc755b629b410303cf105d2a29 -->
## Get the list of terms and privacy policies.

> Example request:

```bash
curl -X GET -G "http://localhost/api/compliance" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/compliance");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
            "term_content": "\r\nScope of service\r\nMonica supports the following browsers:\r\n\r\nInternet Explorer (11+)\r\nFirefox (50+)\r\nChrome (latest)\r\nSafari (latest)\r\nI do not guarantee that the site will work with other browsers, but it’s very likely that it will just work.\r\n\r\nRights\r\nYou don’t have to provide your real name when you register to an account. You do however need a valid email address if you want to upgrade your account to the paid version, or receive reminders by email.\r\n\r\nYou have the right to close your account at any time.\r\n\r\nYou have the right to export your data at any time, in the SQL format.\r\n\r\nYour data will not be intentionally shown to other users or shared with third parties.\r\n\r\nYour personal data will not be shared with anyone without your consent.\r\n\r\nYour data is backed up every hour.\r\n\r\nIf the site ceases operation, you will receive an opportunity to export all your data before the site dies.\r\n\r\nAny new features that affect privacy will be strictly opt-in.\r\n\r\nResponsibilities\r\nYou will not use the site to store illegal information or data under the Canadian law (or any law).\r\n\r\nYou have to be at least 18+ to create an account and use the site.\r\n\r\nYou must not abuse the site by knowingly posting malicious code that could harm you or the other users.\r\n\r\nYou must only use the site to do things that are widely accepted as morally good.\r\n\r\nYou may not make automated requests to the site.\r\n\r\nYou may not abuse the invitation system.\r\n\r\nYou are responsible for keeping your account secure.\r\n\r\nI reserve the right to close accounts that abuse the system (thousands of contacts with hundred of thousands of reminders for instance) or use it in an unreasonable manner.\r\n\r\nOther important legal stuff\r\nThough I want to provide a great service, there are certain things about the service I cannot promise. For example, the services and software are provided “as-is”, at your own risk, without express or implied warranty or condition of any kind. I also disclaim any warranties of merchantability, fitness for a particular purpose or non-infringement. Monica will have no responsibility for any harm to your computer system, loss or corruption of data, or other harm that results from your access to or use of the Services or Software.\r\n\r\nThese Terms can change at any time, but I’ll never be a dick about it. Running this site is a dream come true to me, and I hope I’ll be able to run it as long as I can.\r\n        ",
            "privacy_version": "2",
            "privacy_content": "\r\nMonica is an open source project. The hosted version has a premium plan that let us collect money so we can pay for the servers and additional servers, but the main goal is not to make money (otherwise we wouldn’t have opened source it).\r\n\r\nMonica comes in two flavors: you can either use our hosted version, or download it and run it yourself. In the latter case, we do not track anything at all. We don’t know that you’ve even downloaded the product. Do whatever you want with it (but respect your local laws).\r\n\r\nWhen you create your account on our hosted version, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information.\r\n\r\nWhen you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.\r\n\r\nMonica runs on Linode and we are the only ones, apart from Linode’s employees, who have access to those servers.\r\n\r\nWe do hourly backups of the database.\r\n\r\nYour password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryptions mechanism, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.\r\n\r\nIf a data breach happens, we will contact the users who are affected to warn them about the breach.\r\n\r\nTransactional emails are dserved through Postmark.\r\n\r\nWe use an open source tool called Sentry to track errors that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets me debug what’s going on.\r\n\r\nThe site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.\r\n\r\nWe do no use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data, neither on the marketing site or the hosted version. We are deeply against their principles as they would use those data to profile you, which we are totally against.\r\n\r\nAll the data you put on Monica belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.\r\n\r\nAll the information about the contacts you put on Monica are private to you. We do not cross link information between accounts or use one information in an account to populate another account (unlike Facebook for instance).\r\n\r\nWe use Stripe to collect payments made to access the paid version. We do not store credit card information or anything concerning the transactions themselves on our servers. However, as per the open source library we use to process the payments (Laravel Cashier), we store the last 4 digits of the credit card, the brand name (VISA or MasterCard). As a user, you are identified on Stripe by a random number that they generate and use.\r\n\r\nRegarding the payments, you can downgrade to the free plan whenever you like. When you do, Stripe is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.\r\n\r\nYou can export your data at any time. You can also use the API to export all your data if you know how to do it. You can also request that we process this ourselves and send it to you. Your data will be exported in the SQL format.\r\n\r\nWhen you close your account, we immediately destroy all your personal information and don’t keep any backup. While you have control over this, we can delete an account for you if you ask us.\r\n\r\nIn certain situations, we may be required to disclose peronal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.\r\n\r\nIf you violate the terms of use we will terminate your account and notify you about it. However if you follow the \"don’t be a dick\" policy, nothing should ever happen to you and we’ll all be happy.\r\n\r\nMonica uses only open-source projects that are mainly hosted on Github.\r\n\r\nWe will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.",
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
curl -X GET -G "http://localhost/api/compliance/{compliance}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/compliance/{compliance}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
        "term_content": "\r\nScope of service\r\nMonica supports the following browsers:\r\n\r\nInternet Explorer (11+)\r\nFirefox (50+)\r\nChrome (latest)\r\nSafari (latest)\r\nI do not guarantee that the site will work with other browsers, but it’s very likely that it will just work.\r\n\r\nRights\r\nYou don’t have to provide your real name when you register to an account. You do however need a valid email address if you want to upgrade your account to the paid version, or receive reminders by email.\r\n\r\nYou have the right to close your account at any time.\r\n\r\nYou have the right to export your data at any time, in the SQL format.\r\n\r\nYour data will not be intentionally shown to other users or shared with third parties.\r\n\r\nYour personal data will not be shared with anyone without your consent.\r\n\r\nYour data is backed up every hour.\r\n\r\nIf the site ceases operation, you will receive an opportunity to export all your data before the site dies.\r\n\r\nAny new features that affect privacy will be strictly opt-in.\r\n\r\nResponsibilities\r\nYou will not use the site to store illegal information or data under the Canadian law (or any law).\r\n\r\nYou have to be at least 18+ to create an account and use the site.\r\n\r\nYou must not abuse the site by knowingly posting malicious code that could harm you or the other users.\r\n\r\nYou must only use the site to do things that are widely accepted as morally good.\r\n\r\nYou may not make automated requests to the site.\r\n\r\nYou may not abuse the invitation system.\r\n\r\nYou are responsible for keeping your account secure.\r\n\r\nI reserve the right to close accounts that abuse the system (thousands of contacts with hundred of thousands of reminders for instance) or use it in an unreasonable manner.\r\n\r\nOther important legal stuff\r\nThough I want to provide a great service, there are certain things about the service I cannot promise. For example, the services and software are provided “as-is”, at your own risk, without express or implied warranty or condition of any kind. I also disclaim any warranties of merchantability, fitness for a particular purpose or non-infringement. Monica will have no responsibility for any harm to your computer system, loss or corruption of data, or other harm that results from your access to or use of the Services or Software.\r\n\r\nThese Terms can change at any time, but I’ll never be a dick about it. Running this site is a dream come true to me, and I hope I’ll be able to run it as long as I can.\r\n        ",
        "privacy_version": "2",
        "privacy_content": "\r\nMonica is an open source project. The hosted version has a premium plan that let us collect money so we can pay for the servers and additional servers, but the main goal is not to make money (otherwise we wouldn’t have opened source it).\r\n\r\nMonica comes in two flavors: you can either use our hosted version, or download it and run it yourself. In the latter case, we do not track anything at all. We don’t know that you’ve even downloaded the product. Do whatever you want with it (but respect your local laws).\r\n\r\nWhen you create your account on our hosted version, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information.\r\n\r\nWhen you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.\r\n\r\nMonica runs on Linode and we are the only ones, apart from Linode’s employees, who have access to those servers.\r\n\r\nWe do hourly backups of the database.\r\n\r\nYour password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryptions mechanism, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.\r\n\r\nIf a data breach happens, we will contact the users who are affected to warn them about the breach.\r\n\r\nTransactional emails are dserved through Postmark.\r\n\r\nWe use an open source tool called Sentry to track errors that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets me debug what’s going on.\r\n\r\nThe site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.\r\n\r\nWe do no use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data, neither on the marketing site or the hosted version. We are deeply against their principles as they would use those data to profile you, which we are totally against.\r\n\r\nAll the data you put on Monica belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.\r\n\r\nAll the information about the contacts you put on Monica are private to you. We do not cross link information between accounts or use one information in an account to populate another account (unlike Facebook for instance).\r\n\r\nWe use Stripe to collect payments made to access the paid version. We do not store credit card information or anything concerning the transactions themselves on our servers. However, as per the open source library we use to process the payments (Laravel Cashier), we store the last 4 digits of the credit card, the brand name (VISA or MasterCard). As a user, you are identified on Stripe by a random number that they generate and use.\r\n\r\nRegarding the payments, you can downgrade to the free plan whenever you like. When you do, Stripe is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.\r\n\r\nYou can export your data at any time. You can also use the API to export all your data if you know how to do it. You can also request that we process this ourselves and send it to you. Your data will be exported in the SQL format.\r\n\r\nWhen you close your account, we immediately destroy all your personal information and don’t keep any backup. While you have control over this, we can delete an account for you if you ask us.\r\n\r\nIn certain situations, we may be required to disclose peronal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.\r\n\r\nIf you violate the terms of use we will terminate your account and notify you about it. However if you follow the \"don’t be a dick\" policy, nothing should ever happen to you and we’ll all be happy.\r\n\r\nMonica uses only open-source projects that are mainly hosted on Github.\r\n\r\nWe will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.",
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
curl -X GET -G "http://localhost/api/currencies" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/currencies");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
            "iso": "AED",
            "name": "Emirati Dirham",
            "symbol": ".د.ب"
        },
        {
            "id": 11,
            "object": "currency",
            "iso": "AFN",
            "name": "Afghan Afghani",
            "symbol": "؋"
        },
        {
            "id": 12,
            "object": "currency",
            "iso": "ALL",
            "name": "Albanian lek",
            "symbol": "lek"
        },
        {
            "id": 13,
            "object": "currency",
            "iso": "AMD",
            "name": "Armenian dram",
            "symbol": ""
        },
        {
            "id": 14,
            "object": "currency",
            "iso": "ANG",
            "name": "Dutch Guilder",
            "symbol": "ƒ"
        },
        {
            "id": 15,
            "object": "currency",
            "iso": "AOA",
            "name": "Angolan Kwanza",
            "symbol": "Kz"
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
        "total": 156
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
curl -X GET -G "http://localhost/api/currencies/{currency}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/currencies/{currency}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/countries" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/countries");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
        "AFG": {
            "id": "AF",
            "object": "country",
            "name": "Afghanistan",
            "iso": "AF"
        },
        "WSB": {
            "id": "WSB",
            "object": "country",
            "name": "Akrotiri",
            "iso": "WSB"
        },
        "ALA": {
            "id": "AX",
            "object": "country",
            "name": "Åland Islands",
            "iso": "AX"
        },
        "ALB": {
            "id": "AL",
            "object": "country",
            "name": "Albania",
            "iso": "AL"
        },
        "DZA": {
            "id": "DZ",
            "object": "country",
            "name": "Algeria",
            "iso": "DZ"
        },
        "ASM": {
            "id": "AS",
            "object": "country",
            "name": "American Samoa",
            "iso": "AS"
        },
        "AND": {
            "id": "AD",
            "object": "country",
            "name": "Andorra",
            "iso": "AD"
        },
        "AGO": {
            "id": "AO",
            "object": "country",
            "name": "Angola",
            "iso": "AO"
        },
        "AIA": {
            "id": "AI",
            "object": "country",
            "name": "Anguilla",
            "iso": "AI"
        },
        "ATA": {
            "id": "AQ",
            "object": "country",
            "name": "Antarctica",
            "iso": "AQ"
        },
        "ATG": {
            "id": "AG",
            "object": "country",
            "name": "Antigua and Barbuda",
            "iso": "AG"
        },
        "ARG": {
            "id": "AR",
            "object": "country",
            "name": "Argentina",
            "iso": "AR"
        },
        "ARM": {
            "id": "AM",
            "object": "country",
            "name": "Armenia",
            "iso": "AM"
        },
        "ABW": {
            "id": "AW",
            "object": "country",
            "name": "Aruba",
            "iso": "AW"
        },
        "ATC": {
            "id": "ATC",
            "object": "country",
            "name": "Ashmore and Cartier Is.",
            "iso": "ATC"
        },
        "AUS": {
            "id": "AU",
            "object": "country",
            "name": "Australia",
            "iso": "AU"
        },
        "AUT": {
            "id": "AT",
            "object": "country",
            "name": "Austria",
            "iso": "AT"
        },
        "AZE": {
            "id": "AZ",
            "object": "country",
            "name": "Azerbaijan",
            "iso": "AZ"
        },
        "BHS": {
            "id": "BS",
            "object": "country",
            "name": "Bahamas",
            "iso": "BS"
        },
        "BHR": {
            "id": "BH",
            "object": "country",
            "name": "Bahrain",
            "iso": "BH"
        },
        "KAB": {
            "id": "KAB",
            "object": "country",
            "name": "Baikonur",
            "iso": "KAB"
        },
        "BJN": {
            "id": "BJN",
            "object": "country",
            "name": "Bajo Nuevo Bank",
            "iso": "BJN"
        },
        "BGD": {
            "id": "BD",
            "object": "country",
            "name": "Bangladesh",
            "iso": "BD"
        },
        "BRB": {
            "id": "BB",
            "object": "country",
            "name": "Barbados",
            "iso": "BB"
        },
        "BLR": {
            "id": "BY",
            "object": "country",
            "name": "Belarus",
            "iso": "BY"
        },
        "BEL": {
            "id": "BE",
            "object": "country",
            "name": "Belgium",
            "iso": "BE"
        },
        "BLZ": {
            "id": "BZ",
            "object": "country",
            "name": "Belize",
            "iso": "BZ"
        },
        "BEN": {
            "id": "BJ",
            "object": "country",
            "name": "Benin",
            "iso": "BJ"
        },
        "BMU": {
            "id": "BM",
            "object": "country",
            "name": "Bermuda",
            "iso": "BM"
        },
        "BTN": {
            "id": "BT",
            "object": "country",
            "name": "Bhutan",
            "iso": "BT"
        },
        "BOL": {
            "id": "BO",
            "object": "country",
            "name": "Bolivia",
            "iso": "BO"
        },
        "BIH": {
            "id": "BA",
            "object": "country",
            "name": "Bosnia and Herzegovina",
            "iso": "BA"
        },
        "BWA": {
            "id": "BW",
            "object": "country",
            "name": "Botswana",
            "iso": "BW"
        },
        "BVT": {
            "id": "BV",
            "object": "country",
            "name": "Bouvet Island",
            "iso": "BV"
        },
        "BRA": {
            "id": "BR",
            "object": "country",
            "name": "Brazil",
            "iso": "BR"
        },
        "IOT": {
            "id": "IO",
            "object": "country",
            "name": "British Indian Ocean Territory",
            "iso": "IO"
        },
        "VGB": {
            "id": "VG",
            "object": "country",
            "name": "British Virgin Islands",
            "iso": "VG"
        },
        "BRN": {
            "id": "BN",
            "object": "country",
            "name": "Brunei",
            "iso": "BN"
        },
        "BGR": {
            "id": "BG",
            "object": "country",
            "name": "Bulgaria",
            "iso": "BG"
        },
        "BFA": {
            "id": "BF",
            "object": "country",
            "name": "Burkina Faso",
            "iso": "BF"
        },
        "BDI": {
            "id": "BI",
            "object": "country",
            "name": "Burundi",
            "iso": "BI"
        },
        "KHM": {
            "id": "KH",
            "object": "country",
            "name": "Cambodia",
            "iso": "KH"
        },
        "CMR": {
            "id": "CM",
            "object": "country",
            "name": "Cameroon",
            "iso": "CM"
        },
        "CAN": {
            "id": "CA",
            "object": "country",
            "name": "Canada",
            "iso": "CA"
        },
        "CPV": {
            "id": "CV",
            "object": "country",
            "name": "Cape Verde",
            "iso": "CV"
        },
        "BES": {
            "id": "BQ",
            "object": "country",
            "name": "Caribbean Netherlands",
            "iso": "BQ"
        },
        "CYM": {
            "id": "KY",
            "object": "country",
            "name": "Cayman Islands",
            "iso": "KY"
        },
        "CAF": {
            "id": "CF",
            "object": "country",
            "name": "Central African Republic",
            "iso": "CF"
        },
        "TCD": {
            "id": "TD",
            "object": "country",
            "name": "Chad",
            "iso": "TD"
        },
        "CHL": {
            "id": "CL",
            "object": "country",
            "name": "Chile",
            "iso": "CL"
        },
        "CHN": {
            "id": "CN",
            "object": "country",
            "name": "China",
            "iso": "CN"
        },
        "CXR": {
            "id": "CX",
            "object": "country",
            "name": "Christmas Island",
            "iso": "CX"
        },
        "CLP": {
            "id": "CLP",
            "object": "country",
            "name": "Clipperton I.",
            "iso": "CLP"
        },
        "CCK": {
            "id": "CC",
            "object": "country",
            "name": "Cocos (Keeling) Islands",
            "iso": "CC"
        },
        "COL": {
            "id": "CO",
            "object": "country",
            "name": "Colombia",
            "iso": "CO"
        },
        "COM": {
            "id": "KM",
            "object": "country",
            "name": "Comoros",
            "iso": "KM"
        },
        "COK": {
            "id": "CK",
            "object": "country",
            "name": "Cook Islands",
            "iso": "CK"
        },
        "CSI": {
            "id": "CSI",
            "object": "country",
            "name": "Coral Sea Is.",
            "iso": "CSI"
        },
        "CRI": {
            "id": "CR",
            "object": "country",
            "name": "Costa Rica",
            "iso": "CR"
        },
        "HRV": {
            "id": "HR",
            "object": "country",
            "name": "Croatia",
            "iso": "HR"
        },
        "CUB": {
            "id": "CU",
            "object": "country",
            "name": "Cuba",
            "iso": "CU"
        },
        "CUW": {
            "id": "CW",
            "object": "country",
            "name": "Curaçao",
            "iso": "CW"
        },
        "CYP": {
            "id": "CY",
            "object": "country",
            "name": "Cyprus",
            "iso": "CY"
        },
        "CNM": {
            "id": "CNM",
            "object": "country",
            "name": "Cyprus U.N. Buffer Zone",
            "iso": "CNM"
        },
        "CZE": {
            "id": "CZ",
            "object": "country",
            "name": "Czechia",
            "iso": "CZ"
        },
        "DNK": {
            "id": "DK",
            "object": "country",
            "name": "Denmark",
            "iso": "DK"
        },
        "ESB": {
            "id": "ESB",
            "object": "country",
            "name": "Dhekelia",
            "iso": "ESB"
        },
        "DJI": {
            "id": "DJ",
            "object": "country",
            "name": "Djibouti",
            "iso": "DJ"
        },
        "DMA": {
            "id": "DM",
            "object": "country",
            "name": "Dominica",
            "iso": "DM"
        },
        "DOM": {
            "id": "DO",
            "object": "country",
            "name": "Dominican Republic",
            "iso": "DO"
        },
        "COD": {
            "id": "CD",
            "object": "country",
            "name": "DR Congo",
            "iso": "CD"
        },
        "ECU": {
            "id": "EC",
            "object": "country",
            "name": "Ecuador",
            "iso": "EC"
        },
        "EGY": {
            "id": "EG",
            "object": "country",
            "name": "Egypt",
            "iso": "EG"
        },
        "SLV": {
            "id": "SV",
            "object": "country",
            "name": "El Salvador",
            "iso": "SV"
        },
        "GNQ": {
            "id": "GQ",
            "object": "country",
            "name": "Equatorial Guinea",
            "iso": "GQ"
        },
        "ERI": {
            "id": "ER",
            "object": "country",
            "name": "Eritrea",
            "iso": "ER"
        },
        "EST": {
            "id": "EE",
            "object": "country",
            "name": "Estonia",
            "iso": "EE"
        },
        "ETH": {
            "id": "ET",
            "object": "country",
            "name": "Ethiopia",
            "iso": "ET"
        },
        "EUR": {
            "id": "EU",
            "object": "country",
            "name": "Europe Union",
            "iso": "EU"
        },
        "FLK": {
            "id": "FK",
            "object": "country",
            "name": "Falkland Islands",
            "iso": "FK"
        },
        "FRO": {
            "id": "FO",
            "object": "country",
            "name": "Faroe Islands",
            "iso": "FO"
        },
        "FJI": {
            "id": "FJ",
            "object": "country",
            "name": "Fiji",
            "iso": "FJ"
        },
        "FIN": {
            "id": "FI",
            "object": "country",
            "name": "Finland",
            "iso": "FI"
        },
        "FRA": {
            "id": "FR",
            "object": "country",
            "name": "France",
            "iso": "FR"
        },
        "GUF": {
            "id": "GF",
            "object": "country",
            "name": "French Guiana",
            "iso": "GF"
        },
        "PYF": {
            "id": "PF",
            "object": "country",
            "name": "French Polynesia",
            "iso": "PF"
        },
        "ATF": {
            "id": "TF",
            "object": "country",
            "name": "French Southern and Antarctic Lands",
            "iso": "TF"
        },
        "GAB": {
            "id": "GA",
            "object": "country",
            "name": "Gabon",
            "iso": "GA"
        },
        "GMB": {
            "id": "GM",
            "object": "country",
            "name": "Gambia",
            "iso": "GM"
        },
        "GEO": {
            "id": "GE",
            "object": "country",
            "name": "Georgia",
            "iso": "GE"
        },
        "DEU": {
            "id": "DE",
            "object": "country",
            "name": "Germany",
            "iso": "DE"
        },
        "GHA": {
            "id": "GH",
            "object": "country",
            "name": "Ghana",
            "iso": "GH"
        },
        "GIB": {
            "id": "GI",
            "object": "country",
            "name": "Gibraltar",
            "iso": "GI"
        },
        "GRC": {
            "id": "GR",
            "object": "country",
            "name": "Greece",
            "iso": "GR"
        },
        "GRL": {
            "id": "GL",
            "object": "country",
            "name": "Greenland",
            "iso": "GL"
        },
        "GRD": {
            "id": "GD",
            "object": "country",
            "name": "Grenada",
            "iso": "GD"
        },
        "GLP": {
            "id": "GP",
            "object": "country",
            "name": "Guadeloupe",
            "iso": "GP"
        },
        "GUM": {
            "id": "GU",
            "object": "country",
            "name": "Guam",
            "iso": "GU"
        },
        "GTM": {
            "id": "GT",
            "object": "country",
            "name": "Guatemala",
            "iso": "GT"
        },
        "GGY": {
            "id": "GG",
            "object": "country",
            "name": "Guernsey",
            "iso": "GG"
        },
        "GIN": {
            "id": "GN",
            "object": "country",
            "name": "Guinea",
            "iso": "GN"
        },
        "GNB": {
            "id": "GW",
            "object": "country",
            "name": "Guinea-Bissau",
            "iso": "GW"
        },
        "GUY": {
            "id": "GY",
            "object": "country",
            "name": "Guyana",
            "iso": "GY"
        },
        "HTI": {
            "id": "HT",
            "object": "country",
            "name": "Haiti",
            "iso": "HT"
        },
        "HMD": {
            "id": "HM",
            "object": "country",
            "name": "Heard Island and McDonald Islands",
            "iso": "HM"
        },
        "HND": {
            "id": "HN",
            "object": "country",
            "name": "Honduras",
            "iso": "HN"
        },
        "HKG": {
            "id": "HK",
            "object": "country",
            "name": "Hong Kong",
            "iso": "HK"
        },
        "HUN": {
            "id": "HU",
            "object": "country",
            "name": "Hungary",
            "iso": "HU"
        },
        "ISL": {
            "id": "IS",
            "object": "country",
            "name": "Iceland",
            "iso": "IS"
        },
        "IND": {
            "id": "IN",
            "object": "country",
            "name": "India",
            "iso": "IN"
        },
        "IOA": {
            "id": "IOA",
            "object": "country",
            "name": "Indian Ocean Ter.",
            "iso": "IOA"
        },
        "IDN": {
            "id": "ID",
            "object": "country",
            "name": "Indonesia",
            "iso": "ID"
        },
        "IRN": {
            "id": "IR",
            "object": "country",
            "name": "Iran",
            "iso": "IR"
        },
        "IRQ": {
            "id": "IQ",
            "object": "country",
            "name": "Iraq",
            "iso": "IQ"
        },
        "IRL": {
            "id": "IE",
            "object": "country",
            "name": "Ireland",
            "iso": "IE"
        },
        "IMN": {
            "id": "IM",
            "object": "country",
            "name": "Isle of Man",
            "iso": "IM"
        },
        "ISR": {
            "id": "IL",
            "object": "country",
            "name": "Israel",
            "iso": "IL"
        },
        "ITA": {
            "id": "IT",
            "object": "country",
            "name": "Italy",
            "iso": "IT"
        },
        "CIV": {
            "id": "CI",
            "object": "country",
            "name": "Ivory Coast",
            "iso": "CI"
        },
        "JAM": {
            "id": "JM",
            "object": "country",
            "name": "Jamaica",
            "iso": "JM"
        },
        "JPN": {
            "id": "JP",
            "object": "country",
            "name": "Japan",
            "iso": "JP"
        },
        "JEY": {
            "id": "JE",
            "object": "country",
            "name": "Jersey",
            "iso": "JE"
        },
        "JOR": {
            "id": "JO",
            "object": "country",
            "name": "Jordan",
            "iso": "JO"
        },
        "KAZ": {
            "id": "KZ",
            "object": "country",
            "name": "Kazakhstan",
            "iso": "KZ"
        },
        "KEN": {
            "id": "KE",
            "object": "country",
            "name": "Kenya",
            "iso": "KE"
        },
        "KIR": {
            "id": "KI",
            "object": "country",
            "name": "Kiribati",
            "iso": "KI"
        },
        "UNK": {
            "id": "XK",
            "object": "country",
            "name": "Kosovo",
            "iso": "XK"
        },
        "KWT": {
            "id": "KW",
            "object": "country",
            "name": "Kuwait",
            "iso": "KW"
        },
        "KGZ": {
            "id": "KG",
            "object": "country",
            "name": "Kyrgyzstan",
            "iso": "KG"
        },
        "LAO": {
            "id": "LA",
            "object": "country",
            "name": "Laos",
            "iso": "LA"
        },
        "LVA": {
            "id": "LV",
            "object": "country",
            "name": "Latvia",
            "iso": "LV"
        },
        "LBN": {
            "id": "LB",
            "object": "country",
            "name": "Lebanon",
            "iso": "LB"
        },
        "LSO": {
            "id": "LS",
            "object": "country",
            "name": "Lesotho",
            "iso": "LS"
        },
        "LBR": {
            "id": "LR",
            "object": "country",
            "name": "Liberia",
            "iso": "LR"
        },
        "LBY": {
            "id": "LY",
            "object": "country",
            "name": "Libya",
            "iso": "LY"
        },
        "LIE": {
            "id": "LI",
            "object": "country",
            "name": "Liechtenstein",
            "iso": "LI"
        },
        "LTU": {
            "id": "LT",
            "object": "country",
            "name": "Lithuania",
            "iso": "LT"
        },
        "LUX": {
            "id": "LU",
            "object": "country",
            "name": "Luxembourg",
            "iso": "LU"
        },
        "MAC": {
            "id": "MO",
            "object": "country",
            "name": "Macau",
            "iso": "MO"
        },
        "MKD": {
            "id": "MK",
            "object": "country",
            "name": "Macedonia",
            "iso": "MK"
        },
        "MDG": {
            "id": "MG",
            "object": "country",
            "name": "Madagascar",
            "iso": "MG"
        },
        "MWI": {
            "id": "MW",
            "object": "country",
            "name": "Malawi",
            "iso": "MW"
        },
        "MYS": {
            "id": "MY",
            "object": "country",
            "name": "Malaysia",
            "iso": "MY"
        },
        "MDV": {
            "id": "MV",
            "object": "country",
            "name": "Maldives",
            "iso": "MV"
        },
        "MLI": {
            "id": "ML",
            "object": "country",
            "name": "Mali",
            "iso": "ML"
        },
        "MLT": {
            "id": "MT",
            "object": "country",
            "name": "Malta",
            "iso": "MT"
        },
        "MHL": {
            "id": "MH",
            "object": "country",
            "name": "Marshall Islands",
            "iso": "MH"
        },
        "MTQ": {
            "id": "MQ",
            "object": "country",
            "name": "Martinique",
            "iso": "MQ"
        },
        "MRT": {
            "id": "MR",
            "object": "country",
            "name": "Mauritania",
            "iso": "MR"
        },
        "MUS": {
            "id": "MU",
            "object": "country",
            "name": "Mauritius",
            "iso": "MU"
        },
        "MYT": {
            "id": "YT",
            "object": "country",
            "name": "Mayotte",
            "iso": "YT"
        },
        "MEX": {
            "id": "MX",
            "object": "country",
            "name": "Mexico",
            "iso": "MX"
        },
        "FSM": {
            "id": "FM",
            "object": "country",
            "name": "Micronesia",
            "iso": "FM"
        },
        "MDA": {
            "id": "MD",
            "object": "country",
            "name": "Moldova",
            "iso": "MD"
        },
        "MCO": {
            "id": "MC",
            "object": "country",
            "name": "Monaco",
            "iso": "MC"
        },
        "MNG": {
            "id": "MN",
            "object": "country",
            "name": "Mongolia",
            "iso": "MN"
        },
        "MNE": {
            "id": "ME",
            "object": "country",
            "name": "Montenegro",
            "iso": "ME"
        },
        "MSR": {
            "id": "MS",
            "object": "country",
            "name": "Montserrat",
            "iso": "MS"
        },
        "MAR": {
            "id": "MA",
            "object": "country",
            "name": "Morocco",
            "iso": "MA"
        },
        "MOZ": {
            "id": "MZ",
            "object": "country",
            "name": "Mozambique",
            "iso": "MZ"
        },
        "MMR": {
            "id": "MM",
            "object": "country",
            "name": "Myanmar",
            "iso": "MM"
        },
        "CYN": {
            "id": "CYN",
            "object": "country",
            "name": "N. Cyprus",
            "iso": "CYN"
        },
        "NAM": {
            "id": "NA",
            "object": "country",
            "name": "Namibia",
            "iso": "NA"
        },
        "NRU": {
            "id": "NR",
            "object": "country",
            "name": "Nauru",
            "iso": "NR"
        },
        "NPL": {
            "id": "NP",
            "object": "country",
            "name": "Nepal",
            "iso": "NP"
        },
        "NLD": {
            "id": "NL",
            "object": "country",
            "name": "Netherlands",
            "iso": "NL"
        },
        "NCL": {
            "id": "NC",
            "object": "country",
            "name": "New Caledonia",
            "iso": "NC"
        },
        "NZL": {
            "id": "NZ",
            "object": "country",
            "name": "New Zealand",
            "iso": "NZ"
        },
        "NIC": {
            "id": "NI",
            "object": "country",
            "name": "Nicaragua",
            "iso": "NI"
        },
        "NER": {
            "id": "NE",
            "object": "country",
            "name": "Niger",
            "iso": "NE"
        },
        "NGA": {
            "id": "NG",
            "object": "country",
            "name": "Nigeria",
            "iso": "NG"
        },
        "NIU": {
            "id": "NU",
            "object": "country",
            "name": "Niue",
            "iso": "NU"
        },
        "NFK": {
            "id": "NF",
            "object": "country",
            "name": "Norfolk Island",
            "iso": "NF"
        },
        "PRK": {
            "id": "KP",
            "object": "country",
            "name": "North Korea",
            "iso": "KP"
        },
        "MNP": {
            "id": "MP",
            "object": "country",
            "name": "Northern Mariana Islands",
            "iso": "MP"
        },
        "NOR": {
            "id": "NO",
            "object": "country",
            "name": "Norway",
            "iso": "NO"
        },
        "OMN": {
            "id": "OM",
            "object": "country",
            "name": "Oman",
            "iso": "OM"
        },
        "PAK": {
            "id": "PK",
            "object": "country",
            "name": "Pakistan",
            "iso": "PK"
        },
        "PLW": {
            "id": "PW",
            "object": "country",
            "name": "Palau",
            "iso": "PW"
        },
        "PSE": {
            "id": "PS",
            "object": "country",
            "name": "Palestine",
            "iso": "PS"
        },
        "PAN": {
            "id": "PA",
            "object": "country",
            "name": "Panama",
            "iso": "PA"
        },
        "PNG": {
            "id": "PG",
            "object": "country",
            "name": "Papua New Guinea",
            "iso": "PG"
        },
        "PRY": {
            "id": "PY",
            "object": "country",
            "name": "Paraguay",
            "iso": "PY"
        },
        "PER": {
            "id": "PE",
            "object": "country",
            "name": "Peru",
            "iso": "PE"
        },
        "PHL": {
            "id": "PH",
            "object": "country",
            "name": "Philippines",
            "iso": "PH"
        },
        "PCN": {
            "id": "PN",
            "object": "country",
            "name": "Pitcairn Islands",
            "iso": "PN"
        },
        "POL": {
            "id": "PL",
            "object": "country",
            "name": "Poland",
            "iso": "PL"
        },
        "PRT": {
            "id": "PT",
            "object": "country",
            "name": "Portugal",
            "iso": "PT"
        },
        "PRI": {
            "id": "PR",
            "object": "country",
            "name": "Puerto Rico",
            "iso": "PR"
        },
        "QAT": {
            "id": "QA",
            "object": "country",
            "name": "Qatar",
            "iso": "QA"
        },
        "COG": {
            "id": "CG",
            "object": "country",
            "name": "Republic of the Congo",
            "iso": "CG"
        },
        "REU": {
            "id": "RE",
            "object": "country",
            "name": "Réunion",
            "iso": "RE"
        },
        "ROU": {
            "id": "RO",
            "object": "country",
            "name": "Romania",
            "iso": "RO"
        },
        "RUS": {
            "id": "RU",
            "object": "country",
            "name": "Russia",
            "iso": "RU"
        },
        "RWA": {
            "id": "RW",
            "object": "country",
            "name": "Rwanda",
            "iso": "RW"
        },
        "BLM": {
            "id": "BL",
            "object": "country",
            "name": "Saint Barthélemy",
            "iso": "BL"
        },
        "SHN": {
            "id": "SH",
            "object": "country",
            "name": "Saint Helena, Ascension and Tristan da Cunha",
            "iso": "SH"
        },
        "KNA": {
            "id": "KN",
            "object": "country",
            "name": "Saint Kitts and Nevis",
            "iso": "KN"
        },
        "LCA": {
            "id": "LC",
            "object": "country",
            "name": "Saint Lucia",
            "iso": "LC"
        },
        "MAF": {
            "id": "MF",
            "object": "country",
            "name": "Saint Martin",
            "iso": "MF"
        },
        "SPM": {
            "id": "PM",
            "object": "country",
            "name": "Saint Pierre and Miquelon",
            "iso": "PM"
        },
        "VCT": {
            "id": "VC",
            "object": "country",
            "name": "Saint Vincent and the Grenadines",
            "iso": "VC"
        },
        "WSM": {
            "id": "WS",
            "object": "country",
            "name": "Samoa",
            "iso": "WS"
        },
        "SMR": {
            "id": "SM",
            "object": "country",
            "name": "San Marino",
            "iso": "SM"
        },
        "STP": {
            "id": "ST",
            "object": "country",
            "name": "São Tomé and Príncipe",
            "iso": "ST"
        },
        "SAU": {
            "id": "SA",
            "object": "country",
            "name": "Saudi Arabia",
            "iso": "SA"
        },
        "SCR": {
            "id": "SCR",
            "object": "country",
            "name": "Scarborough Reef",
            "iso": "SCR"
        },
        "SEN": {
            "id": "SN",
            "object": "country",
            "name": "Senegal",
            "iso": "SN"
        },
        "SRB": {
            "id": "RS",
            "object": "country",
            "name": "Serbia",
            "iso": "RS"
        },
        "SER": {
            "id": "SER",
            "object": "country",
            "name": "Serranilla Bank",
            "iso": "SER"
        },
        "SYC": {
            "id": "SC",
            "object": "country",
            "name": "Seychelles",
            "iso": "SC"
        },
        "KAS": {
            "id": "KAS",
            "object": "country",
            "name": "Siachen Glacier",
            "iso": "KAS"
        },
        "SLE": {
            "id": "SL",
            "object": "country",
            "name": "Sierra Leone",
            "iso": "SL"
        },
        "SGP": {
            "id": "SG",
            "object": "country",
            "name": "Singapore",
            "iso": "SG"
        },
        "SXM": {
            "id": "SX",
            "object": "country",
            "name": "Sint Maarten",
            "iso": "SX"
        },
        "SVK": {
            "id": "SK",
            "object": "country",
            "name": "Slovakia",
            "iso": "SK"
        },
        "SVN": {
            "id": "SI",
            "object": "country",
            "name": "Slovenia",
            "iso": "SI"
        },
        "SLB": {
            "id": "SB",
            "object": "country",
            "name": "Solomon Islands",
            "iso": "SB"
        },
        "SOM": {
            "id": "SO",
            "object": "country",
            "name": "Somalia",
            "iso": "SO"
        },
        "SOL": {
            "id": "SOL",
            "object": "country",
            "name": "Somaliland",
            "iso": "SOL"
        },
        "ZAF": {
            "id": "ZA",
            "object": "country",
            "name": "South Africa",
            "iso": "ZA"
        },
        "SGS": {
            "id": "GS",
            "object": "country",
            "name": "South Georgia",
            "iso": "GS"
        },
        "KOR": {
            "id": "KR",
            "object": "country",
            "name": "South Korea",
            "iso": "KR"
        },
        "SSD": {
            "id": "SS",
            "object": "country",
            "name": "South Sudan",
            "iso": "SS"
        },
        "ESP": {
            "id": "ES",
            "object": "country",
            "name": "Spain",
            "iso": "ES"
        },
        "PGA": {
            "id": "PGA",
            "object": "country",
            "name": "Spratly Is.",
            "iso": "PGA"
        },
        "LKA": {
            "id": "LK",
            "object": "country",
            "name": "Sri Lanka",
            "iso": "LK"
        },
        "SDN": {
            "id": "SD",
            "object": "country",
            "name": "Sudan",
            "iso": "SD"
        },
        "SUR": {
            "id": "SR",
            "object": "country",
            "name": "Suriname",
            "iso": "SR"
        },
        "SJM": {
            "id": "SJ",
            "object": "country",
            "name": "Svalbard and Jan Mayen",
            "iso": "SJ"
        },
        "SWZ": {
            "id": "SZ",
            "object": "country",
            "name": "Swaziland",
            "iso": "SZ"
        },
        "SWE": {
            "id": "SE",
            "object": "country",
            "name": "Sweden",
            "iso": "SE"
        },
        "CHE": {
            "id": "CH",
            "object": "country",
            "name": "Switzerland",
            "iso": "CH"
        },
        "SYR": {
            "id": "SY",
            "object": "country",
            "name": "Syria",
            "iso": "SY"
        },
        "TWN": {
            "id": "TW",
            "object": "country",
            "name": "Taiwan",
            "iso": "TW"
        },
        "TJK": {
            "id": "TJ",
            "object": "country",
            "name": "Tajikistan",
            "iso": "TJ"
        },
        "TZA": {
            "id": "TZ",
            "object": "country",
            "name": "Tanzania",
            "iso": "TZ"
        },
        "THA": {
            "id": "TH",
            "object": "country",
            "name": "Thailand",
            "iso": "TH"
        },
        "TLS": {
            "id": "TL",
            "object": "country",
            "name": "Timor-Leste",
            "iso": "TL"
        },
        "TGO": {
            "id": "TG",
            "object": "country",
            "name": "Togo",
            "iso": "TG"
        },
        "TKL": {
            "id": "TK",
            "object": "country",
            "name": "Tokelau",
            "iso": "TK"
        },
        "TON": {
            "id": "TO",
            "object": "country",
            "name": "Tonga",
            "iso": "TO"
        },
        "TTO": {
            "id": "TT",
            "object": "country",
            "name": "Trinidad and Tobago",
            "iso": "TT"
        },
        "TUN": {
            "id": "TN",
            "object": "country",
            "name": "Tunisia",
            "iso": "TN"
        },
        "TUR": {
            "id": "TR",
            "object": "country",
            "name": "Turkey",
            "iso": "TR"
        },
        "TKM": {
            "id": "TM",
            "object": "country",
            "name": "Turkmenistan",
            "iso": "TM"
        },
        "TCA": {
            "id": "TC",
            "object": "country",
            "name": "Turks and Caicos Islands",
            "iso": "TC"
        },
        "TUV": {
            "id": "TV",
            "object": "country",
            "name": "Tuvalu",
            "iso": "TV"
        },
        "UGA": {
            "id": "UG",
            "object": "country",
            "name": "Uganda",
            "iso": "UG"
        },
        "UKR": {
            "id": "UA",
            "object": "country",
            "name": "Ukraine",
            "iso": "UA"
        },
        "ARE": {
            "id": "AE",
            "object": "country",
            "name": "United Arab Emirates",
            "iso": "AE"
        },
        "GBR": {
            "id": "GB",
            "object": "country",
            "name": "United Kingdom",
            "iso": "GB"
        },
        "USA": {
            "id": "US",
            "object": "country",
            "name": "United States",
            "iso": "US"
        },
        "UMI": {
            "id": "UM",
            "object": "country",
            "name": "United States Minor Outlying Islands",
            "iso": "UM"
        },
        "VIR": {
            "id": "VI",
            "object": "country",
            "name": "United States Virgin Islands",
            "iso": "VI"
        },
        "URY": {
            "id": "UY",
            "object": "country",
            "name": "Uruguay",
            "iso": "UY"
        },
        "USG": {
            "id": "USG",
            "object": "country",
            "name": "USNB Guantanamo Bay",
            "iso": "USG"
        },
        "UZB": {
            "id": "UZ",
            "object": "country",
            "name": "Uzbekistan",
            "iso": "UZ"
        },
        "VUT": {
            "id": "VU",
            "object": "country",
            "name": "Vanuatu",
            "iso": "VU"
        },
        "VAT": {
            "id": "VA",
            "object": "country",
            "name": "Vatican City",
            "iso": "VA"
        },
        "VEN": {
            "id": "VE",
            "object": "country",
            "name": "Venezuela",
            "iso": "VE"
        },
        "VNM": {
            "id": "VN",
            "object": "country",
            "name": "Vietnam",
            "iso": "VN"
        },
        "WLF": {
            "id": "WF",
            "object": "country",
            "name": "Wallis and Futuna",
            "iso": "WF"
        },
        "ESH": {
            "id": "EH",
            "object": "country",
            "name": "Western Sahara",
            "iso": "EH"
        },
        "YEM": {
            "id": "YE",
            "object": "country",
            "name": "Yemen",
            "iso": "YE"
        },
        "ZMB": {
            "id": "ZM",
            "object": "country",
            "name": "Zambia",
            "iso": "ZM"
        },
        "ZWE": {
            "id": "ZW",
            "object": "country",
            "name": "Zimbabwe",
            "iso": "ZW"
        }
    }
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
curl -X GET -G "http://localhost/api/notes" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/notes");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/notes?page=1",
        "last": "http:\/\/localhost\/api\/notes?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/notes",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/notes`


<!-- END_5184c63b96049910fee7fc65756de436 -->

<!-- START_fc4b6ae244ae158e33e19e0d56b80711 -->
## Store the note.

> Example request:

```bash
curl -X POST "http://localhost/api/notes" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/notes");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/notes/{note}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/notes/{note}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/notes/{note}`


<!-- END_7c30ddc7968295f3665e9e7e4712506d -->

<!-- START_e17ba28433f1ed23cb7a68ebbfcafa11 -->
## Update the note.

> Example request:

```bash
curl -X PUT "http://localhost/api/notes/{note}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/notes/{note}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/notes/{note}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/notes/{note}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/notes" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/notes");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/occupations" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/occupations");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/occupations?page=1",
        "last": "http:\/\/localhost\/api\/occupations?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/occupations",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/occupations`


<!-- END_44fc9d2f31e34bec296d01a4fd3b4f64 -->

<!-- START_94a278dfa6b18e34c60ccc41fdf72fbb -->
## Store the occupation.

> Example request:

```bash
curl -X POST "http://localhost/api/occupations" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/occupations");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/occupations/{occupation}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/occupations/{occupation}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/occupations/{occupation}`


<!-- END_1a5156301ff2f3cd6b0816ee2303331d -->

<!-- START_9b48b3be226fa34bfad64e60ea8b915f -->
## Update an occupation.

> Example request:

```bash
curl -X PUT "http://localhost/api/occupations/{occupation}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/occupations/{occupation}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/occupations/{occupation}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/occupations/{occupation}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/pets" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/pets");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/pets?page=1",
        "last": "http:\/\/localhost\/api\/pets?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/pets",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/pets`


<!-- END_e0c1b7b4dea17c1d1ff6c3b57f7f4082 -->

<!-- START_cbb5a5c7bc1cde4598c0a554fa1ed829 -->
## Store the pet.

> Example request:

```bash
curl -X POST "http://localhost/api/pets" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/pets");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/pets/{pet}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/pets/{pet}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/pets/{pet}`


<!-- END_7f555f395aff7f195f4c5b5c11d33ccd -->

<!-- START_d20e803533b81c7387387db2c9038e98 -->
## Update the pet.

> Example request:

```bash
curl -X PUT "http://localhost/api/pets/{pet}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/pets/{pet}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/pets/{pet}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/pets/{pet}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/pets" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/pets");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/places" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/places");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/places?page=1",
        "last": "http:\/\/localhost\/api\/places?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/places",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/places`


<!-- END_7e65c6380b56f3836f914d63f895bd42 -->

<!-- START_4c3e06c510713e3a9090bbf1c008203a -->
## Store the place.

> Example request:

```bash
curl -X POST "http://localhost/api/places" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/places");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/places/{place}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/places/{place}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/places/{place}`


<!-- END_dd9635f21ef0081d95c38071781376f5 -->

<!-- START_38859726f694afa752fe160e8c4920da -->
## Update a place.

> Example request:

```bash
curl -X PUT "http://localhost/api/places/{place}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/places/{place}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/places/{place}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/places/{place}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/relationshiptypegroups" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/relationshiptypegroups");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
            "id": 253,
            "object": "relationshiptypegroup",
            "name": "love",
            "delible": false,
            "account": {
                "id": 80
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 254,
            "object": "relationshiptypegroup",
            "name": "family",
            "delible": false,
            "account": {
                "id": 80
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 255,
            "object": "relationshiptypegroup",
            "name": "friend",
            "delible": false,
            "account": {
                "id": 80
            },
            "created_at": null,
            "updated_at": null
        },
        {
            "id": 256,
            "object": "relationshiptypegroup",
            "name": "work",
            "delible": false,
            "account": {
                "id": 80
            },
            "created_at": null,
            "updated_at": null
        }
    ],
    "links": {
        "first": "http:\/\/localhost\/api\/relationshiptypegroups?page=1",
        "last": "http:\/\/localhost\/api\/relationshiptypegroups?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/relationshiptypegroups",
        "per_page": 15,
        "to": 4,
        "total": 4
    }
}
```

### HTTP Request
`GET api/relationshiptypegroups`


<!-- END_01117b30a8e4332606627a000667d176 -->

<!-- START_e211dc0774124cfa939bf1995373aec0 -->
## Get the detail of a given relationship type group.

> Example request:

```bash
curl -X GET -G "http://localhost/api/relationshiptypegroups/{relationshiptypegroup}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/relationshiptypegroups/{relationshiptypegroup}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/relationshiptypes" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/relationshiptypes");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
            "id": 1626,
            "object": "relationshiptype",
            "name": "partner",
            "name_reverse_relationship": "partner",
            "relationship_type_group_id": 261,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1627,
            "object": "relationshiptype",
            "name": "spouse",
            "name_reverse_relationship": "spouse",
            "relationship_type_group_id": 261,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1628,
            "object": "relationshiptype",
            "name": "date",
            "name_reverse_relationship": "date",
            "relationship_type_group_id": 261,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1629,
            "object": "relationshiptype",
            "name": "lover",
            "name_reverse_relationship": "lover",
            "relationship_type_group_id": 261,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1630,
            "object": "relationshiptype",
            "name": "inlovewith",
            "name_reverse_relationship": "lovedby",
            "relationship_type_group_id": 261,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1631,
            "object": "relationshiptype",
            "name": "lovedby",
            "name_reverse_relationship": "inlovewith",
            "relationship_type_group_id": 261,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1632,
            "object": "relationshiptype",
            "name": "ex",
            "name_reverse_relationship": "ex",
            "relationship_type_group_id": 261,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1633,
            "object": "relationshiptype",
            "name": "parent",
            "name_reverse_relationship": "child",
            "relationship_type_group_id": 262,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1634,
            "object": "relationshiptype",
            "name": "child",
            "name_reverse_relationship": "parent",
            "relationship_type_group_id": 262,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1635,
            "object": "relationshiptype",
            "name": "sibling",
            "name_reverse_relationship": "sibling",
            "relationship_type_group_id": 262,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1636,
            "object": "relationshiptype",
            "name": "grandparent",
            "name_reverse_relationship": "grandchild",
            "relationship_type_group_id": 262,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1637,
            "object": "relationshiptype",
            "name": "grandchild",
            "name_reverse_relationship": "grandparent",
            "relationship_type_group_id": 262,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1638,
            "object": "relationshiptype",
            "name": "uncle",
            "name_reverse_relationship": "nephew",
            "relationship_type_group_id": 262,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1639,
            "object": "relationshiptype",
            "name": "nephew",
            "name_reverse_relationship": "uncle",
            "relationship_type_group_id": 262,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        },
        {
            "id": 1640,
            "object": "relationshiptype",
            "name": "cousin",
            "name_reverse_relationship": "cousin",
            "relationship_type_group_id": 262,
            "delible": false,
            "account": {
                "id": 82
            },
            "created_at": "2019-03-26T22:35:48Z",
            "updated_at": "2019-03-26T22:35:48Z"
        }
    ],
    "links": {
        "first": "http:\/\/localhost\/api\/relationshiptypes?page=1",
        "last": "http:\/\/localhost\/api\/relationshiptypes?page=2",
        "prev": null,
        "next": "http:\/\/localhost\/api\/relationshiptypes?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 2,
        "path": "http:\/\/localhost\/api\/relationshiptypes",
        "per_page": 15,
        "to": 15,
        "total": 25
    }
}
```

### HTTP Request
`GET api/relationshiptypes`


<!-- END_aff6c6f170abaa78630729b1a92a50da -->

<!-- START_a1485371ba89d68ffdcf4d043f149286 -->
## Get the detail of a given relationship type.

> Example request:

```bash
curl -X GET -G "http://localhost/api/relationshiptypes/{relationshiptype}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/relationshiptypes/{relationshiptype}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X POST "http://localhost/api/relationships" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/relationships");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/relationships/{relationship}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/relationships/{relationship}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/relationships/{relationship}`


<!-- END_211bdcc77418eb92671e24e623788444 -->

<!-- START_533074e3799d9ee6ec97d4a3d4f1b46b -->
## Update an existing relationship.

> Example request:

```bash
curl -X PUT "http://localhost/api/relationships/{relationship}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/relationships/{relationship}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/relationships/{relationship}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/relationships/{relationship}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/relationships" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/relationships");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": []
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
curl -X GET -G "http://localhost/api/reminders" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/reminders");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/reminders?page=1",
        "last": "http:\/\/localhost\/api\/reminders?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/reminders",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/reminders`


<!-- END_fc8b7b4c9225175cc83fb11f750b3836 -->

<!-- START_0984ac5a08321d0fb88aaaa1789ec5eb -->
## Store the reminder.

> Example request:

```bash
curl -X POST "http://localhost/api/reminders" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/reminders");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/reminders/{reminder}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/reminders/{reminder}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/reminders/{reminder}`


<!-- END_5eb56fa8f5b62926fa180c5662a4eb1b -->

<!-- START_d50c1638768ef084cd00d5a9bde204c0 -->
## Update the reminder.

> Example request:

```bash
curl -X PUT "http://localhost/api/reminders/{reminder}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/reminders/{reminder}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/reminders/{reminder}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/reminders/{reminder}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/reminders" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/reminders");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X POST "http://localhost/api/contacts/{contact}/setTags" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/setTags");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X POST "http://localhost/api/contacts/{contact}/unsetTags" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/unsetTags");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X POST "http://localhost/api/contacts/{contact}/unsetTag" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/unsetTag");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/tags" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tags");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/tags?page=1",
        "last": "http:\/\/localhost\/api\/tags?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/tags",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/tags`


<!-- END_dde6989ab5551d4fb09439f7cb2554c5 -->

<!-- START_6b95d7d1e0e5c34dd24d290bc715dccb -->
## Store the tag.

> Example request:

```bash
curl -X POST "http://localhost/api/tags" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tags");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/tags/{tag}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tags/{tag}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/tags/{tag}`


<!-- END_faf1227d26e1a9f94cda4bb6a688c251 -->

<!-- START_a4a8a57c60acc8638ef566d690878aca -->
## Update the tag.

> Example request:

```bash
curl -X PUT "http://localhost/api/tags/{tag}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tags/{tag}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/tags/{tag}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tags/{tag}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/tasks" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tasks");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "data": [],
    "links": {
        "first": "http:\/\/localhost\/api\/tasks?page=1",
        "last": "http:\/\/localhost\/api\/tasks?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "path": "http:\/\/localhost\/api\/tasks",
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

### HTTP Request
`GET api/tasks`


<!-- END_4227b9e5e54912af051e8dd5472afbce -->

<!-- START_4da0d9b378428dcc89ced395d4a806e7 -->
## Store the task.

> Example request:

```bash
curl -X POST "http://localhost/api/tasks" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tasks");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/tasks/{task}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tasks/{task}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
}
```

### HTTP Request
`GET api/tasks/{task}`


<!-- END_5297efa151ae4fd515fec2efd5cb1e9a -->

<!-- START_546f027bf591f2ef4a8a743f0a59051d -->
## Update the task.

> Example request:

```bash
curl -X PUT "http://localhost/api/tasks/{task}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tasks/{task}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X DELETE "http://localhost/api/tasks/{task}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/tasks/{task}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
curl -X GET -G "http://localhost/api/contacts/{contact}/tasks" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/contacts/{contact}/tasks");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
    "error": {
        "message": "The resource has not been found",
        "error_code": 31
    }
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
curl -X GET -G "http://localhost/api/me" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/me");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
        "id": 9,
        "object": "user",
        "first_name": "Laisha",
        "last_name": "O'Keefe",
        "email": "hayes.amelia@example.com",
        "timezone": "UTC",
        "currency": null,
        "locale": "en",
        "is_policy_compliant": true,
        "account": {
            "id": 25
        },
        "created_at": "2019-03-26T22:35:13Z",
        "updated_at": "2019-03-26T22:35:13Z"
    }
}
```

### HTTP Request
`GET api/me`


<!-- END_b19e2ecbb41b5fa6802edaf581aab5f6 -->

<!-- START_c4fa2f9c78d988b16c201e9beae5059e -->
## Get all the policies ever signed by the authenticated user.

> Example request:

```bash
curl -X GET -G "http://localhost/api/me/compliance" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/me/compliance");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
        [
            {
                "signed": true,
                "signed_date": "2019-03-26T22:35:14Z",
                "ip_address": null,
                "user": {
                    "id": 10,
                    "object": "user",
                    "first_name": "Jadon",
                    "last_name": "Braun",
                    "email": "wilderman.yasmeen@example.com",
                    "timezone": "UTC",
                    "currency": null,
                    "locale": "en",
                    "is_policy_compliant": true,
                    "account": {
                        "id": 26
                    },
                    "created_at": "2019-03-26T22:35:14Z",
                    "updated_at": "2019-03-26T22:35:14Z"
                },
                "term": {
                    "id": 1,
                    "object": "term",
                    "term_version": "2",
                    "term_content": "\r\nScope of service\r\nMonica supports the following browsers:\r\n\r\nInternet Explorer (11+)\r\nFirefox (50+)\r\nChrome (latest)\r\nSafari (latest)\r\nI do not guarantee that the site will work with other browsers, but it’s very likely that it will just work.\r\n\r\nRights\r\nYou don’t have to provide your real name when you register to an account. You do however need a valid email address if you want to upgrade your account to the paid version, or receive reminders by email.\r\n\r\nYou have the right to close your account at any time.\r\n\r\nYou have the right to export your data at any time, in the SQL format.\r\n\r\nYour data will not be intentionally shown to other users or shared with third parties.\r\n\r\nYour personal data will not be shared with anyone without your consent.\r\n\r\nYour data is backed up every hour.\r\n\r\nIf the site ceases operation, you will receive an opportunity to export all your data before the site dies.\r\n\r\nAny new features that affect privacy will be strictly opt-in.\r\n\r\nResponsibilities\r\nYou will not use the site to store illegal information or data under the Canadian law (or any law).\r\n\r\nYou have to be at least 18+ to create an account and use the site.\r\n\r\nYou must not abuse the site by knowingly posting malicious code that could harm you or the other users.\r\n\r\nYou must only use the site to do things that are widely accepted as morally good.\r\n\r\nYou may not make automated requests to the site.\r\n\r\nYou may not abuse the invitation system.\r\n\r\nYou are responsible for keeping your account secure.\r\n\r\nI reserve the right to close accounts that abuse the system (thousands of contacts with hundred of thousands of reminders for instance) or use it in an unreasonable manner.\r\n\r\nOther important legal stuff\r\nThough I want to provide a great service, there are certain things about the service I cannot promise. For example, the services and software are provided “as-is”, at your own risk, without express or implied warranty or condition of any kind. I also disclaim any warranties of merchantability, fitness for a particular purpose or non-infringement. Monica will have no responsibility for any harm to your computer system, loss or corruption of data, or other harm that results from your access to or use of the Services or Software.\r\n\r\nThese Terms can change at any time, but I’ll never be a dick about it. Running this site is a dream come true to me, and I hope I’ll be able to run it as long as I can.\r\n        ",
                    "privacy_version": "2",
                    "privacy_content": "\r\nMonica is an open source project. The hosted version has a premium plan that let us collect money so we can pay for the servers and additional servers, but the main goal is not to make money (otherwise we wouldn’t have opened source it).\r\n\r\nMonica comes in two flavors: you can either use our hosted version, or download it and run it yourself. In the latter case, we do not track anything at all. We don’t know that you’ve even downloaded the product. Do whatever you want with it (but respect your local laws).\r\n\r\nWhen you create your account on our hosted version, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information.\r\n\r\nWhen you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.\r\n\r\nMonica runs on Linode and we are the only ones, apart from Linode’s employees, who have access to those servers.\r\n\r\nWe do hourly backups of the database.\r\n\r\nYour password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryptions mechanism, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.\r\n\r\nIf a data breach happens, we will contact the users who are affected to warn them about the breach.\r\n\r\nTransactional emails are dserved through Postmark.\r\n\r\nWe use an open source tool called Sentry to track errors that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets me debug what’s going on.\r\n\r\nThe site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.\r\n\r\nWe do no use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data, neither on the marketing site or the hosted version. We are deeply against their principles as they would use those data to profile you, which we are totally against.\r\n\r\nAll the data you put on Monica belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.\r\n\r\nAll the information about the contacts you put on Monica are private to you. We do not cross link information between accounts or use one information in an account to populate another account (unlike Facebook for instance).\r\n\r\nWe use Stripe to collect payments made to access the paid version. We do not store credit card information or anything concerning the transactions themselves on our servers. However, as per the open source library we use to process the payments (Laravel Cashier), we store the last 4 digits of the credit card, the brand name (VISA or MasterCard). As a user, you are identified on Stripe by a random number that they generate and use.\r\n\r\nRegarding the payments, you can downgrade to the free plan whenever you like. When you do, Stripe is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.\r\n\r\nYou can export your data at any time. You can also use the API to export all your data if you know how to do it. You can also request that we process this ourselves and send it to you. Your data will be exported in the SQL format.\r\n\r\nWhen you close your account, we immediately destroy all your personal information and don’t keep any backup. While you have control over this, we can delete an account for you if you ask us.\r\n\r\nIn certain situations, we may be required to disclose peronal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.\r\n\r\nIf you violate the terms of use we will terminate your account and notify you about it. However if you follow the \"don’t be a dick\" policy, nothing should ever happen to you and we’ll all be happy.\r\n\r\nMonica uses only open-source projects that are mainly hosted on Github.\r\n\r\nWe will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.",
                    "created_at": "2018-04-12T00:00:00Z",
                    "updated_at": null
                }
            }
        ]
    ]
}
```

### HTTP Request
`GET api/me/compliance`


<!-- END_c4fa2f9c78d988b16c201e9beae5059e -->

<!-- START_5edb4677580f92db7359521b6afd2964 -->
## Get the state of a specific term for the user.

> Example request:

```bash
curl -X GET -G "http://localhost/api/me/compliance/{id}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/me/compliance/{id}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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
        "signed": true,
        "signed_date": "2019-03-26T22:35:15Z",
        "ip_address": null,
        "user": {
            "id": 11,
            "object": "user",
            "first_name": "Sedrick",
            "last_name": "Kerluke",
            "email": "bailey.wava@example.net",
            "timezone": "UTC",
            "currency": null,
            "locale": "en",
            "is_policy_compliant": true,
            "account": {
                "id": 27
            },
            "created_at": "2019-03-26T22:35:14Z",
            "updated_at": "2019-03-26T22:35:14Z"
        },
        "term": {
            "id": 1,
            "object": "term",
            "term_version": "2",
            "term_content": "\r\nScope of service\r\nMonica supports the following browsers:\r\n\r\nInternet Explorer (11+)\r\nFirefox (50+)\r\nChrome (latest)\r\nSafari (latest)\r\nI do not guarantee that the site will work with other browsers, but it’s very likely that it will just work.\r\n\r\nRights\r\nYou don’t have to provide your real name when you register to an account. You do however need a valid email address if you want to upgrade your account to the paid version, or receive reminders by email.\r\n\r\nYou have the right to close your account at any time.\r\n\r\nYou have the right to export your data at any time, in the SQL format.\r\n\r\nYour data will not be intentionally shown to other users or shared with third parties.\r\n\r\nYour personal data will not be shared with anyone without your consent.\r\n\r\nYour data is backed up every hour.\r\n\r\nIf the site ceases operation, you will receive an opportunity to export all your data before the site dies.\r\n\r\nAny new features that affect privacy will be strictly opt-in.\r\n\r\nResponsibilities\r\nYou will not use the site to store illegal information or data under the Canadian law (or any law).\r\n\r\nYou have to be at least 18+ to create an account and use the site.\r\n\r\nYou must not abuse the site by knowingly posting malicious code that could harm you or the other users.\r\n\r\nYou must only use the site to do things that are widely accepted as morally good.\r\n\r\nYou may not make automated requests to the site.\r\n\r\nYou may not abuse the invitation system.\r\n\r\nYou are responsible for keeping your account secure.\r\n\r\nI reserve the right to close accounts that abuse the system (thousands of contacts with hundred of thousands of reminders for instance) or use it in an unreasonable manner.\r\n\r\nOther important legal stuff\r\nThough I want to provide a great service, there are certain things about the service I cannot promise. For example, the services and software are provided “as-is”, at your own risk, without express or implied warranty or condition of any kind. I also disclaim any warranties of merchantability, fitness for a particular purpose or non-infringement. Monica will have no responsibility for any harm to your computer system, loss or corruption of data, or other harm that results from your access to or use of the Services or Software.\r\n\r\nThese Terms can change at any time, but I’ll never be a dick about it. Running this site is a dream come true to me, and I hope I’ll be able to run it as long as I can.\r\n        ",
            "privacy_version": "2",
            "privacy_content": "\r\nMonica is an open source project. The hosted version has a premium plan that let us collect money so we can pay for the servers and additional servers, but the main goal is not to make money (otherwise we wouldn’t have opened source it).\r\n\r\nMonica comes in two flavors: you can either use our hosted version, or download it and run it yourself. In the latter case, we do not track anything at all. We don’t know that you’ve even downloaded the product. Do whatever you want with it (but respect your local laws).\r\n\r\nWhen you create your account on our hosted version, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information.\r\n\r\nWhen you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.\r\n\r\nMonica runs on Linode and we are the only ones, apart from Linode’s employees, who have access to those servers.\r\n\r\nWe do hourly backups of the database.\r\n\r\nYour password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryptions mechanism, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.\r\n\r\nIf a data breach happens, we will contact the users who are affected to warn them about the breach.\r\n\r\nTransactional emails are dserved through Postmark.\r\n\r\nWe use an open source tool called Sentry to track errors that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets me debug what’s going on.\r\n\r\nThe site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.\r\n\r\nWe do no use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data, neither on the marketing site or the hosted version. We are deeply against their principles as they would use those data to profile you, which we are totally against.\r\n\r\nAll the data you put on Monica belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.\r\n\r\nAll the information about the contacts you put on Monica are private to you. We do not cross link information between accounts or use one information in an account to populate another account (unlike Facebook for instance).\r\n\r\nWe use Stripe to collect payments made to access the paid version. We do not store credit card information or anything concerning the transactions themselves on our servers. However, as per the open source library we use to process the payments (Laravel Cashier), we store the last 4 digits of the credit card, the brand name (VISA or MasterCard). As a user, you are identified on Stripe by a random number that they generate and use.\r\n\r\nRegarding the payments, you can downgrade to the free plan whenever you like. When you do, Stripe is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.\r\n\r\nYou can export your data at any time. You can also use the API to export all your data if you know how to do it. You can also request that we process this ourselves and send it to you. Your data will be exported in the SQL format.\r\n\r\nWhen you close your account, we immediately destroy all your personal information and don’t keep any backup. While you have control over this, we can delete an account for you if you ask us.\r\n\r\nIn certain situations, we may be required to disclose peronal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.\r\n\r\nIf you violate the terms of use we will terminate your account and notify you about it. However if you follow the \"don’t be a dick\" policy, nothing should ever happen to you and we’ll all be happy.\r\n\r\nMonica uses only open-source projects that are mainly hosted on Github.\r\n\r\nWe will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.",
            "created_at": "2018-04-12T00:00:00Z",
            "updated_at": null
        }
    }
}
```

### HTTP Request
`GET api/me/compliance/{id}`


<!-- END_5edb4677580f92db7359521b6afd2964 -->

<!-- START_93263693603c69f0172b2474dd65074c -->
## Sign the latest policy for the authenticated user.

> Example request:

```bash
curl -X POST "http://localhost/api/me/compliance" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://localhost/api/me/compliance");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

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



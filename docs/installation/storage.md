# External storage <!-- omit in toc -->

- [Configure an external storage](#configure-an-external-storage)
- [Add an external storage](#add-an-external-storage)
  - [1. Create AWS S3 storage](#1-create-aws-s3-storage)
  - [2. Create a user](#2-create-a-user)
  - [3. Set environment variables](#3-set-environment-variables)
  - [(Optional) Use another S3 provider](#optional-use-another-s3-provider)
  - [Move avatars to S3 storage](#move-avatars-to-s3-storage)


Some times you want to add an external storage for your avatars, photos, or documents.

This is useful in particular if you install Monica on a stateless volatile instance, like Heroku, Platform.sh, etc.

We currently only support AWS S3 driver as external storage.


## Configure an external storage

You need to define at least these environment variables:

```
DEFAULT_FILESYSTEM=s3
AWS_BUCKET=
AWS_DEFAULT_REGION=
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

See below for more details about each environment variable.


## Add an external storage

### 1. Create AWS S3 storage

1. Go to the S3 [console](https://s3.console.aws.amazon.com/s3/home)
2. Add a new bucket
3. Save the name and location of the bucket in `AWS_BUCKET` and `AWS_DEFAULT_REGION` variables

```
AWS_BUCKET=my-bucket
AWS_DEFAULT_REGION=eu-west-3
```

You can also use [AWS CLI](https://docs.aws.amazon.com/cli/index.html) to create the bucket:
```sh
aws s3 mb s3://my-bucket
```

### 2. Create a user

1. Create a new user via the [console](https://console.aws.amazon.com/iam/home#/users).
2. Add the strategy for S3 access, for instance `AmazonS3FullAccess` is a good choice:
   - add the user to a group with the right strategy
   - or attach the strategy directly.

3. Save credentials of the user in `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` variables

```
AWS_ACCESS_KEY_ID=AKXA3E2NYF7NPDJVQSOU
AWS_SECRET_ACCESS_KEY=aASalDme6wB8kGC7Xla6K3pI+FiFylpCVnGCmdnD
```

You can also use [AWS CLI](https://docs.aws.amazon.com/cli/index.html) to set credentials:
```sh
aws iam create-user --user-name user-monica-test
aws iam attach-user-policy --user-name user-monica-test --policy-arn arn:aws:iam::aws:policy/AmazonS3FullAccess
aws iam create-access-key --user-name user-monica-test
```
Output:
```
{
    "AccessKey": {
        "UserName": "user-monica-test",
        "AccessKeyId": "AKIAXE2N0F6NIMZXLCGB",
        "Status": "Active",
        "SecretAccessKey": "Lh5ValIoe9xlfrhkpqiZOub1TFFo4qn1sAdFvlOM",
        "CreateDate": "2020-04-12T11:35:06+00:00"
    }
}
```
Then save `AccessKeyId` and `SecretAccessKey` in `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` variables.

### 3. Set environment variables

Set the `DEFAULT_FILESYSTEM` variable to use S3 storage:
```
DEFAULT_FILESYSTEM=s3
```


### (Optional) Use another S3 provider

*AWS_ENDPOINT* variable can be used to define a S3-compatible provider other than Amazon, like [Digitalocean](https://www.digitalocean.com/products/spaces/), [Scaleway](https://www.scaleway.com) or [Minio](https://min.io/).
   example: `AWS_ENDPOINT=nyc3.digitaloceanspaces.com`


### Move avatars to S3 storage

If you previously used local storage and want to move all avatars to a new S3 storage, use `monica:moveavatars` command once to move all files:
```sh
php artisan monica:moveavatars
```

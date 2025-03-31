# Weekie Mochi API
Weekie Mochi is a 2chan-like app: It allows users to create posts, user profiles, comments and allows users to react to comments.<br>
Weekie Mochi works with JSON WEB TOKEN's.
## Business Rules
- **Profiles**:
    - Only users with a profile can access to the endpoints of the api.
    - All User Profiles require a username, email and password.
    - The username of a user must not start with a number.
    - The username of a user can be, at most, 30 characters, and it can contain unicode characters.
    - The email of a user can be, at most, 100 characters, and it must not contain unicode characters.
    - The password of a user can be, at most, 30 characters, and it can contain unicode characters.
    - The inclusion of a custom profile picture is optional. A User will have a blank picture like other social media apps (facebook, etc.) by default.
    - In case there is the inclusion of a custom profile picture: The allowed formats are: "jpeg", "jpg", "webp", "gif" or "png".

- **Posts**:
    - All posts require a header and a description.
    - The header of a post can be, at most, 100 characters, and it can contain unicode characters.
    - The description of a post can be, at most, 1000 characters, and it can contain unicode characters.
    - The inclusion of image(s) is optional. A post can have no images.
    - In case there is the inclusion of image(s): The allowed formats are: "jpeg", "jpg", "webp", "gif" or "png". No more than 3 images can be uploaded by post.

- **Comments**:
    - All comments require a description.
    - The description of a comment can be, at most, 300 characters, and it can contain unicode characters.
    - The inclusion of image(s) is optional. A comment can have no images.
    - In case there is the inclusion of image(s): The allowed formats are: "jpeg", "jpg", "webp", "gif" or "png". No more than 3 images can be uploaded by comment.


## Tools Used:
This api was developed in **Plain/Vanilla PHP**, no third-party libraries used.

## Documentation
You have access to many endpoints. In the following document, we will show the endpoints available in the API, as well as their requirements and expected responses; They will be split into 2 categories:
1. **Authenticated Users**
    - Requests that can be made with no Authorization Headers sent.
2. **Unauthenticated Users**
    - Requests that can only be made with Authorization Headers sent.<br>

At the same time, the endpoints will be separated by entity in the database to facilitate an object-oriented comprehension.

## Unauthenticated Users
### <u>Users</u>
#### 1. Create User Profile
It allows you to create a user profile. The requirements vary whether you upload or not a custom profile picture.<br>

**POST Request to endpoint:**<br>
/users/signup

- **Profile with no custom profile picture requirements**:
    - Build a JSON body with the mandatory data for a user: username, email, password.
    - Send the JSON body directly as a JSON object to the endpoint. <br>

**Example :**
```
{
    "username": "test1",
    "email": "test1@example.ca",
    "password": "test1pass"
}
```
- **Profile with custom profile picture requirements**:
    - Build a form-data object.
    - Append a JSON body to the form-data object with the mandatory data for a user: username, email, password.<br>
    *[The appended json body **must** be named "jsonBody"]*
    - Append the corresponding image to the form-data object. <br>
    *[The appended image **must** be named "picture"]*

**Expected response:**<br>
If everything went well, the API should send back the basic information of the created user and a **201** status code.
```
[Content-Type: "application/json"]
{
    "userId": 17,
    "username": "test1",
    "picture": "blank-profile-picture.webp",
    "joinedAt": "2025-03-30 13:30:56",
    "amountOfPosts": 0,
    "hierarchyLevelId": 2
}
```
**Expected exception response:**<br>
If any of the fields of the given data is invalid, the API should send back a JSON body with the information of the error and a **400** status code.
```
[Content-Type: "application/json"]
{
    "errors": ["error1", "error2", "error3"]
}
```
**Note:**  
*[You can send a form-data object in both scenarios. However, the appended JSON object **must** be named "jsonBody"]*

#### 2. Log In with user profile
It allows you to log in with user credentials. The requirements don't vary depending on the situation.<br>

**POST Request to endpoint:**<br>
/users/login

- **Requirements**:
    - Build a JSON body with the mandatory data to log in: username, email.
    - Send the JSON body directly as a JSON object to the endpoint. <br>

**Example :**
```
{
    "email": "test1@example.ca",
    "password": "test1pass"
}
```

**Expected response:**<br>
If everything went well, the API should send back a json body similar to the following and a **200** status code.
```
[Content-Type: "application/json"]
{
    "message": "User logged in successfully",
    "token": "abc1234567890"
}
```

**Expected exception response:**<br>
If the user credentials are invalid, the API should send back a JSON body with the information of the error and a **400** status code.
```
[Content-Type: "application/json"]
{
    "errors": ["error1"]
}
```

**Note:**<br> 
*[The **token** will be different than the example]*<br> 
*[You can also send a form-data object. However, the appended json object **must** be named "jsonBody]*



## Authenticated Users
*[All requests made from here on need to be sent along with an **Authorization Header**, the value of this Header must be the **token** the API sent back to the client when logging in]*
### <u>Users</u>
#### 1. Log Out
It allows you to log out. The requirements don't vary depending on the situation.<br>

**POST Request to endpoint:**<br>
/users/logout

- **Requirements**:
    - Do not send any bodies nor objects.

**Expected response:**<br>
If everything went well, the api should send back a json body similar to the following and a **200** status code.
```
[Content-Type: "application/json"]
{
    "message": "User logged out successfully"
}
```

**Expected exception response:**<br>
*[This endpoint does not count with a specific exception response]*

**Note:**  
*[You can send an object (json or form-data). However, it will not be taken into account because it is not useful in this request]*


#### 2. Get information of the logged-in profile
It allows you get the information of the user in session. The requirements don't vary depending on the situation.<br>

**GET Request to endpoint:**<br>
/users/my/profile

- **Requirements**:
    - Do not send any bodies nor objects.

**Expected response:**<br>
If everything went well, the api should send back a json body with the information of the logged-in user and a **200** status code.
```
[Content-Type: "application/json"]
{
    "userId": 17,
    "username": "test1",
    "email": "test1@example.ca",
    "picture": "blank-profile-picture.webp",
    "joinedAt": "2025-03-30 13:30:56",
    "amountOfPosts": 0,
    "hierarchyLevelId": 2
}
```

**Expected exception response:**<br>
*[This endpoint does not count with a specific exception response]*

**Note:**  
*[You can send an object (json or form-data). However, it will not be taken into account because it is not useful in this request]*

#### 3. Edit information of the logged-in profile
It allows you edit the information of the user in session. The requirements vary whether you upload or not a custom profile picture.<br>

**POST Request to endpoint:**<br>
/users/my/profile

- **Update with no custom profile picture requirements**:
    - Build a JSON body with any fields you want to update in the profile: username, email or password.
    - Send the JSON body directly as a JSON object to the endpoint.

**Example:**
```
[Content-Type: "application/json"]
{
    "username": "test1updated",
    "password": "test1passupdated"
}
```
- **Update with custom profile picture requirements**:
    - Build a form-data object.
    - Append a JSON body to the form-data object with any fields you want to update in the profile: username, email or password.<br>
    *[The appended json body **must** be named "jsonBody"]*
    - Append the corresponding image to the form-data object. <br>
    *[The appended image **must** be named "picture"]*

**Expected response:**<br>
If everything went well, the api should send back a json body with the information of the user and a **200** status code.
```
[Content-Type: "application/json"]
{
    "userId": 17,
    "username": "test1updated",
    "email": "test1@example.ca",
    "picture": "blank-profile-picture.webp",
    "joinedAt": "2025-03-30 13:30:56",
    "amountOfPosts": 0,
    "hierarchyLevelId": 2
}
```

**Expected exception response:**<br>
If any of the fields intended to update is invalid, the API should send back a JSON body with the information of the error and a **400** status code.
```
[Content-Type: "application/json"]
{
    "errors": ["error1", "error2", "error3"]
}
```
**Note:**<br>
*[You can send no objects (neither JSON nor form-data). The profile will still be updated, but with no changes as no data was given]*<br>
*[In case any of the fields is given, the API will validate properly each of them]*<br>
*[You can send a form-data object in both scenarios. However, the appended JSON object **must** be named "jsonBody"]*

#### 4. Delete the logged-in profile
It allows you to delete the logged-in user. The requirements don't vary depending on the situation.<br>

**DELETE Request to endpoint:**<br>
/users/my/profile

- **Requirements**:
    - Do not send any bodies nor objects.
    - The "hierarchyLevelId" field of your profile **must** be 2. <br>
    *[2 -> <u>Regular User</u>, 1 -> <u>Administrator</u>]*.

**Expected response:**<br>
If everything went well, the API should send back a JSON body similar to the following and a **200** status code.
```
[Content-Type: "application/json"]
{
    "message": [
        "Your profile was deleted successfully"
    ]
}
```

**Expected exception response:**<br>
If the "hierarchyLevelId" field of your profile is 1, it implies you are an administrator. An Administrator is forbbiden to delete their profile. The API should send back a JSON body similar to the following and a **403** status code.
```
[Content-Type: "application/json"]
{
    "errors": ["Administrators Cannot delete their own profile"]
}
```
**Note:**  
*[You can send an object (json or form-data). However, it will not be taken into account because it is not useful in this request]*

#### 5. Get information of a user
It allows you to get the information of any user registered in the weekieMochi Database. The requirements don't vary depending on the situation.<br>

**GET Request to endpoint:**<br>
/users/:id<br>
*[":id" being a placeholder for the user id]*

- **Requirements**:
    - Do not send any bodies nor objects.

**Expected response:**<br>
If everything went well, the api should send back a json body with the information of the user and a **200** status code.
```
[Content-Type: "application/json"]
[path: "/users/10"]
{
    "userId": 10,
    "username": "张三丰",
    "picture": "profile-picture-ni6392bf93023.jpg",
    "joinedAt": "2025-03-30 12:37:50",
    "amountOfPosts": 2,
    "hierarchyLevelId": 2
}
```

**Expected exception response:**<br>
If no user with the given id exists, the API should send back a JSON body with the information of the error and a **404** status code.
```
[Content-Type: "application/json"]
[path: "fakeid"]
{
    "errors": ["User with id fakeid was not found"]
}
```
**Note:**  
*[You can send an object (json or form-data). However, it will not be taken into account because it is not useful in this request]*

#### 6. Get profile picture of a user
It allows you to get the profile picture of any user registered in the weekieMochi Database. The requirements don't vary depending on the situation.<br>

**GET Request to endpoint:**<br>
/users/:id/pictures<br>
*[":id" being a placeholder for the user id]*

- **Requirements**:
    - Do not send any bodies nor objects.

**Expected response:**<br>
If everything went well, the api should send back the picture of the user and a **200** status code.
```
[Content-Type: "image/gif"]
[path: "/users/666/pictures"]
\Profile picture of the user/
```

**Expected exception response:**<br>
If the picture of the user does exist, but the server is not able to find it, the API should send back an image explaining the error and a **404** status code.
```
[Content-Type: "image/gif"]
[path: "/users/667/pictures"]
\Exception Image/
```
**Note:**  
*[The Content-Type header will not always be "image/gif", it will vary depending on what the MIME type of the picture is, which, at the same time, will be aligned to the allowed formats; In the case of the 404 error, it will always be "image/webp" unless it is changed by developers.]*<br>
*[You can send an object (json or form-data). However, it will not be taken into account because it is not useful in this request]*

#### 7. Get blank user profile picture
It allows you to get a picture of a blank user (like on other social media apps; e.g. Facebook and similars). The requirements don't vary depending on the situation.<br>

**GET Request to endpoint:**<br>
/users/zero/pictures

- **Requirements**:
    - Do not send any bodies nor objects.

**Expected response:**<br>
If everything went well, the api should send back a picture of a blank user and a **200** status code.
```
[Content-Type: "image/webp"]
\Blank User profile picture/
```

**Note:**  
*[The Content-Type header will always be "image/webp" unless it is changed by developers.]*<br>
*[You can send an object (json or form-data). However, it will not be taken into account because it is not useful in this request]*

#### 8. Delete profile of a user
It allows you to delete the profile of any user. The requirements don't vary depending on the situation.<br>

**DELETE Request to endpoint:**<br>
/users/:id<br>
*["id" being a placeholder for the user id]*

- **Requirements**:
    - Do not send any bodies nor objects.
    - The "hierarchyLevelId" field of your profile **must** be 1.<br>
    *[2 -> <u>Regular User</u>, 1 -> <u>Administrator</u>].*<br>
    *[**JUST** an Administrator has access to this endpoint].*
    - The "userId" field of your profile **must** be different than the id sent in the path. <br>
    *[An Administrator **cannot** delete their own profile. To do so, they need to execute a query in the database manually].*
    - The "hierarchyLevelId" field of the profile of the user with the id sent in the path **must** be 2.<br>
    *[An Administrator can **just** delete Regular User profiles, not Administrator profiles].*

**Expected response:**<br>
If everything went well, the API should send back a picture of a blank user and a **200** status code.
```
[Content-Type: "application/json"]
[path: "/users/999"]
{
    "message": "User with 999 deleted successfully"
}
```

**Expected response 1:**<br>
If the "hierarchyLevelId" field of your profile is not 1, the API should send back no bodies and a **403** status code.
```
[Content-Type: "application/json"]
```

**Expected response 2:**<br>
If the "userId" field of your profile is the same as the id sent in the path, the API should send back a JSON body similar to the following a **403** status code.
```
[Content-Type: "application/json"]
{
    "errors": ["Administrators Cannot delete their own profile"]
}
```

**Expected response 3:**<br>
If the "hierarchyLevelId" field of the profile of the user with the id sent in the path is not 2, the API should send back a JSON body similar to the following a **403** status code.
```
[Content-Type: "application/json"]
{
    "errors": ["Cannot delete other administrators' profiles"]
}
```

**Note:**  
*[You can send an object (json or form-data). However, it will not be taken into account because it is not useful in this request]*

### We keep working on giving all the endpoints information as fast as possible...
# Weekie Mochi API
Weekie Mochi is a 2chan-like app: It allows users to create posts, user profiles, comments and allows users to react to comments.

## Business Rules
- **Profiles**:
    - Only users with a profile can access to the endpoints of the API.
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
This API was developed in **Plain/Vanilla PHP**, no third-party libraries used.

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
    *[The appended JSON body **must** be named "jsonBody"]*
    - Append the corresponding image to the form-data object. <br>
    *[The appended image **must** be named "picture"]*

**Expected response:**<br>
If everything went well, the API should send back the basic information of the created user and a **201** status code.
```
{
    "userId": 17,
    "username": "test1",
    "picture": "blank-profile-picture.webp",
    "joinedAt": "2025-03-30 13:30:56",
    "amountOfPosts": 0,
    "hierarchyLevelId": 2
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
If everything went well, the API should send back a JSON body similar to the following and a **200** status code.
```
{
    "message": "User logged in successfully",
    "token": "abc1234567890"
}
```
**Note:**<br> 
*[The **token** will be different than the example]*<br> 
*[You can also send a form-data object. However, the appended JSON object **must** be named "jsonBody]*



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
If everything went well, the API should send back a JSON body similar to the following and a **200** status code.
```
{
    "message": "User logged out successfully"
}
```
**Note:**  
*[You can send an object (JSON or form-data). However, it will not be taken into account because it is not useful in this request]*
### We keep working on giving all the endpoints information as fast as possible...
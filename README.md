# Weekie Mochi API
Weekie Mochi is a 2chan-like app: It allows users to create posts, user profiles, comments and allows users to react to comments.

## Business Rules
- **Profiles**:
    - Only users with a profile can access to the endpoints of the api.
    - A User Profile require a username, email and password.
    - The username of a user must not start with a number.
    - The username of a user can be, at most, 30 characters, and it can contain unicode characters.
    - The email of a user can be, at most, 100 characters, and it must not contain unicode characters.
    - The password of a user can be, at most, 100 characters, and it must not contain unicode characters.
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
we are working on it...
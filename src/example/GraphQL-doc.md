#This project's Type, Query and Mutation documentation
        
* [Types](#types)
* [Queries](#queries)
* [Mutations](#mutations)
        
#Types
        
* [user](#user-type)
* [user_profile](#user_profile-type)
* [location](#location-type)

### <a name="user-type"></a>user
A user
- **id** (Int!): ID of the user
- **email** (String): Email of the user
- **avatar** (String): Avatar (picture) of the user
- **cover** (String): Cover (picture) of the user
- **confirmed** (Boolean): Confirmed status of the user
- **pin** (String): Pin (ID code) of the user
- **profile** ([User profile](#user_profile-type)): User profile

### <a name="user_profile-type"></a>user_profile
A user's profile
- **user_id** (Int!): User id
- **first_name** (String!): First name of the user
- **last_name** (String!): Last name of the user
- **birth_date** (Int): Birth date as timestamp
- **iban** (String): IBAN of the user
- **phone** (String): Phone number
- **height** (Float): Height (in cm)
- **location** ([Location](#location-type)): Location of the user

### <a name="location-type"></a>location
A location on the map
- **id** (Int!): Id of the location
- **country_code** (String!): Country code of the location (e.g "EE")
- **address** (String): Location's address (street, house nr, etc)
- **city** (String): Location's city
- **post_code** (Int): Post code of the location
- **latitude** (Float): Latitude of the location
- **longitude** (Float): Longitude of the location


        
#Queries
* [users](#users-query)
* [user](#user-query)

### <a name="users-query"></a>users
- ids: [Int]

Returns [User](#user-type)

### <a name="user-query"></a>user
- id: Int

Returns [User](#user-type)


        
#Mutations
* [login](#login-mutation)

### <a name="login-mutation"></a>login
- email: String! **(required, email)**
- password: String! **(required, string)**
- remember_me: Boolean **(boolean)**

Returns [User](#user-type)


        

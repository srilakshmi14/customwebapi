This module provides the REST endpoint for node related operations like GET, POST, PATCH and DELETE.

The response provides custom error and success code for each request.

The Success and Error codes used here are defined as follows:

s1: Content Found

s2: Content created

s3: Content updated.

s4: Content deleted

e1: Content not found

e2: No value for required field 

e3: Invalied data type 

Create a content type with machine name 'certification_course' and should contain the following fields (machine name):

title
field_amount (field type: Integer)
field_location (field type: plain text)
field_name_of_the_director (field type: plain text)
field_valied_years (field type: plain text)

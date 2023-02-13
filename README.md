# LACL - Laravel Access Control List

**WORK IN PROGRESS**

Implementation of Access Control List System in Laravel.

The system	handles  authorizations  of  certain  actions  based  on  roles  and
permissions. Permissions are assigned  to  roles,  and	roles  are	assigned  to
users. If  a  user's  role	has  the  matching	permission,  then  the	user  is
authorized to perform  the	given  action;	else  the  user  is  forbidden.  The
permissions can be arbitrarily defined by the application developers.

The system can handle resource-based  permissions,	that  is:  a  permission  is
associated with a resource/model/entity in the database. Thus,	it	is	possible
to, for example, define authorization for a user to edit all  articles	or	just
the articles he has created  by  creating  permissions	for  those	articles  in
particular.


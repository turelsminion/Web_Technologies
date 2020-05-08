create
    definer = netinfo@`%` procedure AddTournament(IN in_name varchar(255), IN in_description text, IN in_date date,
                                                  IN in_time time, IN in_registration_date date,
                                                  IN in_city varchar(100), IN in_address varchar(128),
                                                  IN in_geo varchar(128), IN in_country_id int,
                                                  IN in_poster varchar(128), IN in_type int,
                                                  IN in_organizer_token varchar(128))
begin
    set @coach_id = 0;
    set @role_id = 0;
    SELECT id , role_id
      into @coach_id, @role_id
      from users
      where api_token = in_organizer_token collate utf8mb4_unicode_ci
      ;

    insert into tournaments (name, description, date, time, registration_date, city, address, geo, country, poster, type, organizer_id)
    values
    (ifnull(in_name, 'NOT SET'),
    in_description,
    ifnull(in_date, curdate()),
    ifnull(in_time, curtime()),
    ifnull(in_registration_date, curdate()),
    ifnull(in_city, 'NOT SET'),
    in_address,
    in_geo,
    ifnull(in_country_id, 3831),
    ifnull(in_poster,ifnull(poster,'icon-tournament.png')),
    if(@role_id = 5, 3, ifnull(in_type,1)),
    @coach_id);

    select
		id,
        name as tournament_name,
        date as start_date,
        time as start_time,
        poster,
        geo,
        type as tournament_type,
        address,
        country as country_id,
        organizer_id as organiser_id
		  from tournaments
		 where id = last_insert_id()
    ;
    set @coach_id = null;
end;

create
    definer = netinfo@`%` procedure RemoveTournamentById(IN _tournament_id int)
BEGIN

DELETE FROM tournament_categories
where tournament_id = _tournament_id
;
DELETE FROM tournament_brackets
where tournament_id = _tournament_id
;
DELETE FROM tournament_bracket_schemas_rounds
where tournament_id = _tournament_id
;
DELETE FROM tournament_bracket_schemas
where tournament_id = _tournament_id
;
DELETE FROM tournament_coaches
where tournament_id = _tournament_id
;

DELETE FROM tournament_documents
where tournament_id = _tournament_id
;

DELETE FROM tournament_participants_categories
where tournament_id  = _tournament_id
;

DELETE FROM tournament_participants_data
where tournament_id = _tournament_id
;

DELETE FROM tournament_tatamies_categories
where tournament_id = _tournament_id
;

DELETE FROM tournament_winners
where tournament_id = _tournament_id
;

DELETE FROM tournaments
where id = _tournament_id
;
END;

create
    definer = netinfo@`%` procedure UpdateTournament(IN in_tournament_id int, IN in_name varchar(250),
                                                     IN in_description varchar(500), IN in_date date, IN in_time time,
                                                     IN in_registration_date date, IN in_city varchar(100),
                                                     IN in_address varchar(250), IN in_geo varchar(250),
                                                     IN in_country_id int, IN in_poster varchar(150), IN in_type int,
                                                     IN in_user_token varchar(100))
BEGIN
	select id, role_id into @organizer_id_exists, @user_role from users where api_token = in_user_token collate utf8mb4_unicode_ci;
    select id into @tournament_exists from tournaments where id = in_tournament_id and (@user_role = 1 or organizer_id = @organizer_id_exists);
    if @tournament_exists is not null
    then
        begin
            update tournaments
            set name = ifnull(in_name, name),
            description = ifnull(in_description, description),
            date = ifnull(in_date, date),
            time = ifnull(in_time, time),
            registration_date = ifnull(in_registration_date, registration_date),
            city = ifnull(in_city, city),
            address = ifnull(in_address, address),
            geo = ifnull(in_geo, geo),
            country = ifnull(in_country_id, country),
            poster = ifnull(in_poster, poster),
            type = ifnull(in_type, type)
            where id = in_tournament_id;
            select 1 tournament_update;
        end;
    else
        select 0 tournament_update;
    end if;
    set @tournament_exists = null, @organizer_id_exists = null, @user_role = null;
END;



create
    definer = netinfo@`%` procedure GetTournament(IN in_tournament_id int, IN in_user_id int, IN in_country int)
    comment 'Shows tournaments info by given ID or Org_ID or Country, In case if NULL is coming the procedure returns all tournaments info'
BEGIN

#---- check role administrator
set @l_role := null;

SELECT role_id
 into @l_role
 from users u
 where u.id = in_user_id
;

SELECT
       t.id,
       t.name as tournament_name,
       t.date start_date,
       TIME_FORMAT(t.time, "%H:%i") as start_time,
       t.geo,
       t.type as type,
       t.registration_date,
       concat('/images/tournament_banners/',ifnull(poster,'icon_tournament.png')) poster,
      (SELECT COUNT(tc.coaches_id)      FROM tournament_coaches tc WHERE tc.tournament_id = t.id and tc.application_status = 1) as coaches_count,
      (SELECT COUNT(distinct tpc.participant_id) FROM participants p join tournament_participants_categories tpc on p.id = tpc.participant_id where tpc.tournament_id = t.id and tpc.active = 1 and p.active = 1) as participants_count,
      (SELECT COUNT(p.gender)           FROM tournament_participants_data as tpd, participants as p  WHERE tpd.tournament_id = t.id and tpd.participant_id = p.id and p.gender='M' and tpd.active = 1 and p.active = 1) as male_count,
      (SELECT COUNT(p.gender)           FROM tournament_participants_data as tpd, participants as p  WHERE tpd.tournament_id = t.id and tpd.participant_id = p.id and p.gender='F' and tpd.active = 1 and p.active = 1) as female_count,
	  (SELECT MIN(p.age)                FROM tournament_participants_data as tpd, participants as p  WHERE tpd.tournament_id = t.id and tpd.participant_id = p.id and tpd.active = 1 and p.active = 1) as most_youngest,
 	  (SELECT MAX(p.age)                FROM tournament_participants_data as tpd, participants as p  WHERE tpd.tournament_id = t.id and tpd.participant_id = p.id and tpd.active = 1 and p.active = 1) as most_oldest,
      (SELECT COUNT(tc.category_id)     FROM tournament_categories as tc WHERE tc.tournament_id = t.id) as categories_count,
	  (SELECT COUNT(ttc.tatami_id)      FROM tournament_tatamies_categories as ttc WHERE ttc.tournament_id = t.id) as tatami_count,
       ifnull((SELECT COUNT(distinct p.country) FROM participants p, tournament_participants_categories tpc   WHERE tpc.participant_id = p.id AND tpc.tournament_id = t.id and tpc.active = 1 group by t.country),0) as countries_count,
       tt.name as tournament_type,
       t.organizer_id,
       u.first_name as organizer_fname,
       u.last_name as organizer_lname,
       c.id as c_id, c.name as country,
       c.iso3,
       t.city,
       t.address,
       t.description,
       ( t.organizer_id = in_user_id ) is_master,
       ifnull(( select coaches_id > 0 from tournament_coaches where coaches_id = in_user_id and tournament_id = t.id),0) is_coach
       /*(
            case
                when (@l_role = 'administrator')
                    then 1
                when (in_user_id = ifnull(tc.coaches_id,0))
                    then 2
                when (in_user_id = t.organizer_id)
                    then 3
            end
       )`user_status`*/
FROM tournaments t
     join countries c on t.country = c.id
     join tournament_types tt on t.type = tt.id
     join users u on u.id = t.organizer_id
     left join tournament_coaches tc2 on tc2.coaches_id = in_user_id and tournament_id = t.id
  WHERE 1=1
    AND t.active = 1
	AND (in_tournament_id is NULL OR t.id = in_tournament_id)
    AND (in_tournament_id is not NULL and if(in_user_id is not null and (select role_id = 6 from users where id = in_user_id) and t.type=3, 1, 0 )
        OR in_tournament_id is not NULL and if(in_user_id is not null and (select role_id = 3 from users where id = in_user_id) and t.type!=3, 1, 0 )
        OR (@l_role = 1
             or ( (@l_role = 2 or @l_role = 5) and t.organizer_id = in_user_id )
             or ( tc2.application_status = 1 and (@l_role = 3 or @l_role = 6) and (select count(tpd.tournament_id) > 0 from tournament_participants_data tpd join participants p on tpd.participant_id = p.id where p.coach_id = in_user_id))
             or in_user_id is null and type != 3 and !is_finished)
        )
    AND (in_country is NULL OR t.country = in_country)
    order by date asc
;
END;


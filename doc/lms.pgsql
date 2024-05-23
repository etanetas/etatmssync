CREATE TABLE IF NOT EXISTS public.tms_settings (
    id integer NOT NULL,
    host character varying(255) NOT NULL,
    "user" character varying(255) NOT NULL,
    passwd character varying(255) NOT NULL,
    provider integer NOT NULL
);

DO
$$
BEGIN
  CREATE SEQUENCE public.tms_settings_id_seq;
  ALTER SEQUENCE public.tms_settings_id_seq OWNED BY public.tms_settings.id;

  ALTER TABLE ONLY public.tms_settings ALTER COLUMN id SET DEFAULT nextval('public.tms_settings_id_seq'::regclass);

  ALTER TABLE ONLY public.tms_settings 
      ADD CONSTRAINT tms_settings_pkey PRIMARY KEY (id);
EXCEPTION WHEN duplicate_table THEN
  -- do nothing, it's already there
END
$$ LANGUAGE plpgsql;


CREATE TABLE IF NOT EXISTS public.tms_plans (
    id integer NOT NULL,
    tmstarif character varying(255) NOT NULL,
    lmstarif character varying(255) NOT NULL
);

DO
$$
BEGIN
  CREATE SEQUENCE public.tms_plans_id_seq;
  ALTER SEQUENCE public.tms_plans_id_seq OWNED BY public.tms_plans.id;

  ALTER TABLE ONLY public.tms_plans ALTER COLUMN id SET DEFAULT nextval('public.tms_plans_id_seq'::regclass);

  ALTER TABLE ONLY public.tms_plans
      ADD CONSTRAINT tms_plans_pkey PRIMARY KEY (id);
EXCEPTION WHEN duplicate_table THEN
  -- do nothing, it's already there
END
$$ LANGUAGE plpgsql;

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_EtaTmsSync', '2019050100');
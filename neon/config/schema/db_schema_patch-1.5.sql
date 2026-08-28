ALTER TABLE specprocessorprojects
ADD cleaningmode VARCHAR(10) DEFAULT 'regex'
AFTER projecttype;

ALTER TABLE specprocessorprojects
ADD aiExampleIdentifiers TEXT AFTER cleaningmode;

ALTER TABLE specprocessorprojects
ADD aiExtraInstructions TEXT AFTER aiExampleIdentifiers;
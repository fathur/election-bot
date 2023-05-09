from masoniteorm.connections import ConnectionResolver

DATABASES = {
    "default": "sqlite",
    "mysql": {
        "host": "127.0.0.1",
        "driver": "mysql",
        "database": "masonite",
        "user": "root",
        "password": "",
        "port": 3306,
        "log_queries": False,
        "options": {
            #
        },
    },
    "postgres": {
        "host": "127.0.0.1",
        "driver": "postgres",
        "database": "masonite",
        "user": "root",
        "password": "",
        "port": 5432,
        "log_queries": False,
        "options": {
            #
        },
    },
    "sqlite": {
        "driver": "sqlite",
        "database": "pemilu.sqlite3",
    },
}

DB = ConnectionResolver().set_connection_details(DATABASES)

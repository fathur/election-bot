import os

import sentry_sdk
from loguru import logger

from app import console
from dotenv import load_dotenv

from app.config.log import LOG

sentry_sdk.init(
    dsn=os.getenv("SENTRY_DSN"),
    # Set traces_sample_rate to 1.0 to capture 100%
    # of transactions for performance monitoring.
    # We recommend adjusting this value in production.
    traces_sample_rate=1.0,
)

if __name__ == '__main__':
    load_dotenv()

    logger.add(LOG["location"])

    with logger.catch():
        console.run()

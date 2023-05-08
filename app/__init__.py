import os

import tweepy
from dotenv import load_dotenv
from requests import Response

from app.poll_maker import PollMaker


def run():
    load_dotenv()

    client = tweepy.Client(
        consumer_key=os.getenv("TWITTER_API_KEY"),
        consumer_secret=os.getenv("TWITTER_API_KEY_SECRET"),
        access_token=os.getenv("TWITTER_ACCESS_TOKEN"),
        access_token_secret=os.getenv("TWITTER_ACCESS_TOKEN_SECRET"),
        bearer_token=os.getenv("TWITTER_BEARER_TOKEN"),
    )

    PollMaker(client).run()

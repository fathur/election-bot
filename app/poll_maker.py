import string
from enum import Enum

import pendulum
import numpy as np

from tweepy import Client, tweet as t_tweet

from app.models import Tweet, Poll, PollChoice, PollResult, Account


class Category(Enum):
    CANDIDATE = 1
    PARTY = 2
    MEDIA = 3


class QueryBuilder:
    MY_USERNAME = "__rohman__"

    KEYWORDS = [
        "anies",
        "baswedan",
        "ganjar",
        "pranowo",
        "prabowo",
        "subianto",
        # "presiden",
        "capres",
        "cawapres",
        "pilpres",
        "election",
        "2024",
        "pemilu",
    ]

    USERNAMES = {
        "aniesbaswedan": Category.CANDIDATE,
        "prabowo": Category.CANDIDATE,
        "ganjarpranowo": Category.CANDIDATE,
        "tvOneNews": Category.MEDIA,
        "NasDem": Category.PARTY,
        "PDemokrat": Category.PARTY,
        "MataNajwa": Category.MEDIA,
        "TirtoID": Category.MEDIA,
        "VIVAcoid": Category.MEDIA,
        "CNNIndonesia": Category.MEDIA,
        # "korantempo": Category.MEDIA,
        # "temponewsroom": Category.MEDIA,
        "tempodotco": Category.MEDIA,
        "PKSejahtera": Category.PARTY,
        "PDI_Perjuangan": Category.PARTY,
        "DPP_PPP": Category.PARTY,
        "psi_id": Category.PARTY,
        "hanura_official": Category.PARTY,
        "Gerindra": Category.PARTY,
        "Official_PAN": Category.PARTY,
        "PartaiPerindo": Category.PARTY,
        "OfficialDPP_PBB": Category.PARTY,
        "kumparan": Category.MEDIA,
        "kompascom": Category.MEDIA,
        # "jawapos": Category.MEDIA,
        "tribunnews": Category.MEDIA,
        "liputan6dotcom": Category.MEDIA,
        "Beritasatu": Category.MEDIA,
        "okezonenews": Category.MEDIA,
        "antaranews": Category.MEDIA,
        "BBCIndonesia": Category.MEDIA,
        "voaindonesia": Category.MEDIA,
        "cnbcindonesia": Category.MEDIA,
        "detikcom": Category.MEDIA,
    }

    def for_media(self):
        return f"{self.keywords_statement} {self.target_statement(Category.MEDIA)} -is:retweet -is:reply -is:quote"

    def for_candidates(self):
        return f"{self.target_statement(Category.CANDIDATE)} -is:retweet -is:reply -is:quote"

    def for_parties(self):
        return f"{self.keywords_statement} {self.target_statement(Category.PARTY)} -is:retweet -is:reply -is:quote"

    def for_me(self):
        return f"from:{self.MY_USERNAME} OR from:PemiluKitaBot"

    @property
    def keywords_statement(self):
        joined_keywords = " OR ".join(self.KEYWORDS)
        return f"({joined_keywords})"

    def target_statement(self, category):
        media = []
        for username in self.USERNAMES:
            if self.USERNAMES[username] == category:
                media.append(f"from:{username}")

        joined_usernames = " OR ".join(media)
        return f"({joined_usernames})"


class PollMaker:
    SEARCH_LAST_MINUTES = 20
    POLL_DURATION_MINUTES = 24 * 60

    def __init__(self, client: Client):
        self.client = client

        self.poll_choices = PollChoice.all()

        candidates = self.poll_choices.pluck("option").serialize()
        candidates = np.array(candidates)
        np.random.shuffle(candidates)
        self.candidates = candidates

    def run(self):
        loop = True
        next_token = None
        while loop:
            response = self.client.search_recent_tweets(
                query=self.build_query(),
                start_time=pendulum.now().subtract(minutes=self.SEARCH_LAST_MINUTES),
                end_time=pendulum.now().subtract(seconds=15),
                sort_order="recency",
                next_token=next_token,
                user_auth=True,
                user_fields="id,username,name",
                expansions="author_id",
                max_results=100,
            )

            meta = response.meta
            data = response.data
            print(data)
            if data is not None:
                self.decide_to_post_poll(data)

            if "next_token" in meta:
                next_token = meta["next_token"]
            else:
                loop = False

    def build_query(self):
        # return QueryBuilder().for_me()
        return QueryBuilder().for_candidates()
        # QueryBuilder().for_parties()

    def set_poll(self, tweet: Tweet):
        if self.has_poll(tweet):
            return

        response = self.client.create_tweet(
            in_reply_to_tweet_id=tweet.object_id,
            text="Siapakah calon presiden pilihamu di 2024? Vote sebagai bentuk kepedulianmu terhadap pemilu ini!",
            poll_options=self.candidates.tolist(),
            poll_duration_minutes=self.POLL_DURATION_MINUTES,
            user_auth=True,
        )

        self.insert_poll_to_db(response, tweet)

    def insert_poll_to_db(self, response, tweet):
        object_id = response.data["id"]
        poll = Poll.create(
            {
                "tweet_id": tweet.id,
                "object_id": object_id,
                "url": f"https://twitter.com/PemiluKitaBot/status/{object_id}",
                "start_at": pendulum.now().to_datetime_string(),
                "end_at": pendulum.now()
                .add(minutes=self.POLL_DURATION_MINUTES)
                .to_datetime_string(),
            }
        )
        for choice in self.poll_choices:
            PollResult.create({"poll_id": poll.id, "poll_choice_id": choice.id})

    def decide_to_post_poll(self, data):
        for item in data:
            tweet = Tweet.where({"object_id": item.id}).first()
            if not tweet:
                tweet_detail = self.client.get_tweet(
                    item.id,
                    user_auth=True,
                    user_fields="id,username,name",
                    expansions="author_id",
                )
                user = tweet_detail.includes["users"][0]

                account = Account.where({"object_id": user.id}).first()
                if not account:
                    account = Account.create(
                        {
                            "object_id": user.id,
                            "username": user.username,
                            "name": user.name,
                        }
                    )

                tweet = Tweet.create(
                    {
                        "account_id": account.id,
                        "object_id": item.id,
                        "text": item.text,
                        "url": f"https://twitter.com/{account.username}/status/{item.id}",
                    }
                )

            if self.has_poll(tweet):
                continue

            self.set_poll(tweet)

    def has_poll(self, tweet: Tweet):
        return Poll.where({"tweet_id": tweet.id}).exists()

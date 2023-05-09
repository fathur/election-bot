from enum import Enum

import pendulum
import numpy as np

from tweepy import Client

from app.models import Tweet, Poll, PollChoice, PollResult, Account
from loguru import logger


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
        "capres",
        "cawapres",
        "pilpres",
        "election",
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

    @classmethod
    def for_media(cls):
        klass = cls()
        return f"{klass.keywords_statement} {klass.target_statement(Category.MEDIA)} -is:retweet -is:reply -is:quote"

    @classmethod
    def for_candidates(cls):
        klass = cls()
        return f"{klass.target_statement(Category.CANDIDATE)} -is:retweet -is:reply"

    @classmethod
    def for_parties(cls):
        klass = cls()
        return f"{klass.keywords_statement} {klass.target_statement(Category.PARTY)} -is:retweet -is:reply -is:quote"

    @classmethod
    def for_me(cls):
        klass = cls()
        return f"from:{klass.MY_USERNAME} OR from:PemiluKitaBot"

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
    SEARCH_LAST_MINUTES = 30
    POLL_DURATION_MINUTES = 24 * 60
    NOT_CHOOSE_STRING = "Belum ada pilihan"

    def __init__(self, client: Client):
        self.client = client

        self.poll_choices = PollChoice.all()
        self.candidates = self.poll_choices.pluck("option").serialize()

    def run(self):
        logger.info("Running poll maker")

        self._execute_run(query=QueryBuilder.for_candidates())
        self._execute_run(query=QueryBuilder.for_media())

        logger.info("Finish run poll maker")

    def set_poll(self, tweet: Tweet):
        if self.has_poll(tweet):
            return

        logger.info("Creating poll")

        response = self.client.create_tweet(
            in_reply_to_tweet_id=tweet.object_id,
            text=(
                "Siapakah calon presiden pilihanmu di 2024? "
                "Vote sebagai bentuk kepedulianmu terhadap pemilu ini! \n\n"
                "Retweet untuk menyebarkan, dan beri 🧡 jika bermanfaat."
            ),
            poll_options=self.shuffle_candidates(),
            poll_duration_minutes=self.POLL_DURATION_MINUTES,
            user_auth=False,
        )

        self.insert_poll_to_db(response, tweet)
        logger.info("Created poll")

    def shuffle_candidates(self) -> list[str]:
        append = None
        if self.NOT_CHOOSE_STRING in self.candidates:
            append = self.NOT_CHOOSE_STRING
            self.candidates.remove(self.NOT_CHOOSE_STRING)

        candidates = np.array(self.candidates)
        np.random.shuffle(candidates)
        candidates = candidates.tolist()
        if append is not None:
            candidates.append(append)

        return candidates

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
        logger.info("Running decide to post poll")
        for item in data:
            logger.info({"Checking item": item.id})
            tweet = Tweet.where({"object_id": item.id}).first()
            if not tweet:
                tweet_detail = self.client.get_tweet(
                    item.id,
                    user_auth=False,
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

                logger.info(f"Storing Tweet {item.id}")
                tweet = Tweet.create(
                    {
                        "account_id": account.id,
                        "object_id": item.id,
                        "text": item.text,
                        "url": f"https://twitter.com/{account.username}/status/{item.id}",
                    }
                )
                logger.info(f"Stored Tweet {item.id}")

            if self.has_poll(tweet):
                continue

            self.set_poll(tweet)
            logger.info({"Checked item": item.id})

        logger.info("Finish run decide to post poll")

    def has_poll(self, tweet: Tweet):
        return Poll.where({"tweet_id": tweet.id}).exists()

    def _execute_run(self, query):
        logger.info({"Executing query": query})

        loop = True
        next_token = None
        while loop:
            response = self.client.search_recent_tweets(
                query=query,
                start_time=pendulum.now().subtract(minutes=self.SEARCH_LAST_MINUTES),
                end_time=pendulum.now().subtract(seconds=15),
                sort_order="recency",
                next_token=next_token,
                user_auth=False,
                user_fields="id,username,name",
                expansions="author_id",
                max_results=100,
            )

            meta = response.meta
            data = response.data
            logger.info(data)
            if data is not None:
                self.decide_to_post_poll(data)

            if "next_token" in meta:
                next_token = meta["next_token"]
            else:
                loop = False

        logger.info({"Executed query": query})

import copy

import pendulum
from loguru import logger

from app.models import Poll as PollTweet, PollResult, PollChoice
from tweepy import Poll, Tweet

from app.services.clients import twitter


class Report:
    def __init__(self):
        self.client = twitter()

    @classmethod
    def run(cls, interval):
        klass = cls()
        return getattr(klass, interval)(pendulum.datetime(2023, 5, 10))

    def daily(self, end_at=None):
        if end_at is None:
            end = pendulum.now().subtract(days=1)
        else:
            end = end_at

        tweets = (
            PollTweet.where("end_at", "<", end.to_datetime_string())
            .where_null("total_voter")
            .order_by("end_at")
            .get()
        )
        count = len(tweets)
        i = 0
        first_tweet = None
        last_tweet = None
        ids = []
        map_tweet_poll = {}
        map_poll_tweet = {}
        for tweet in tweets:
            if i == 0:
                first_tweet = tweet
            if i == (count - 1):
                last_tweet = tweet
            ids.append(tweet.object_id)
        tweets = self.client.get_tweets(
            ids,
            expansions=["attachments.poll_ids"],
            poll_fields=[
                "duration_minutes",
                "end_datetime",
                "id",
                "options",
                "voting_status",
            ],
        )

        data = tweets.data
        for item in data:
            map_tweet_poll[item.id] = item.attachments["poll_ids"][0]
            pt = PollTweet.where({"object_id": item.id}).first()
            map_poll_tweet[item.attachments["poll_ids"][0]] = pt.id

        polls = tweets.includes["polls"]
        logger.info(polls)
        for poll in polls:
            poll_id = poll.id  # real poll id
            poll_options = poll.options

            poll_duration = poll.duration_minutes
            poll_end_at = poll.end_datetime
            poll_status = poll.voting_status

            tweet = ""  # Grab the Tweet object

            logger.info({"poll_id": poll.id, "status": poll_status})

            if poll_status == "closed":
                logger.info({"poll_id": poll.id, "options": poll_options})
                for option in poll_options:
                    position = option["position"]
                    label = option["label"]
                    votes = option["votes"]
                    logger.info({"poll_id": poll.id, "option": label})
                    choice = PollChoice.where({"option": label}).first()
                    logger.info({"choice": choice.id})

                    result = PollResult.where(
                        {
                            "poll_id": map_poll_tweet[
                                poll_id
                            ],  # tweet id camouflaged as poll id
                            "poll_choice_id": choice.id,
                        }
                    )

                    logger.info(
                        {
                            "poll_id": map_poll_tweet[poll_id],
                            "poll_choice_id": choice.id,
                        }
                    )

                    result.update({"total_voter": votes})

                sum_voter = (
                    PollResult.where(
                        {
                            "poll_id": map_poll_tweet[poll_id],
                        }
                    )
                    .sum("total_voter")
                    .first()
                    .total_voter
                )
                pt = PollTweet.find(map_poll_tweet[poll_id])
                pt.update({"total_voter": sum_voter})

    def weekly(self):
        pass

    def monthly(self):
        pass

    def quarterly(self):
        pass

    def yearly(self):
        pass

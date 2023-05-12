import copy
import json
import math

import pendulum
from loguru import logger
from masoniteorm.exceptions import QueryException

from app.models import Poll as PollTweet, PollResult, PollChoice
from tweepy import Poll, Tweet

from app.models.Report import Report
from app.models.ReportWinner import ReportWinner
from app.services.clients import twitter


class Reporting:
    def __init__(self):
        self.client = twitter()

    @classmethod
    def run(cls, interval):
        klass = cls()
        return getattr(klass, interval)(pendulum.datetime(2023, 5, 13))

    def daily(self, end_at=None):
        if end_at is None:
            end = pendulum.now().subtract(days=1)
        else:
            end = end_at

        poll_tweets = (
            PollTweet.where("end_at", "<", end.to_datetime_string())
            .where_null("total_voter")
            .order_by("end_at")
            .get()
        )
        count = len(copy.deepcopy(poll_tweets))
        logger.info(f"Count: {count}")

        if count == 0:
            return
        first_tweet = copy.deepcopy(poll_tweets)[0]
        last_tweet = copy.deepcopy(poll_tweets)[-1]
        ids = []
        map_tweet_poll = {}
        map_poll_tweet = {}
        for poll_tweet in poll_tweets:
            ids.append(poll_tweet.object_id)

        twitter_limit_ids = 100
        loop_times = 0
        logger.info({"ids": ids})
        if len(ids) == 0:
            return
        elif len(ids) > twitter_limit_ids:
            loop_times = math.ceil(len(ids) / twitter_limit_ids)
        else:
            loop_times = 1

        logger.info(f"Loop times: {loop_times}")
        start = 0
        end = twitter_limit_ids
        for _ in range(0, loop_times):
            tweets = self.client.get_tweets(
                ids[start:end],
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

            start += twitter_limit_ids
            end += twitter_limit_ids

        logger.info("Creating report")
        related_tweets = poll_tweets.pluck("id").serialize()
        related_tweets = list(map(str, related_tweets))
        related_tweets = ",".join(related_tweets)
        report = Report.create(
            {
                "interval": "daily",
                "start_at": first_tweet.start_at,
                "end_at": last_tweet.end_at,
                # "total_voters": "",
                "related_tweets": related_tweets,  # Tweet id foreign key, that contain poll
                # "winner_resume": {}
            }
        )
        report_voters = 0
        resume = {}
        for choice in PollChoice.all():
            tv = (
                PollResult.where_in("poll_id", poll_tweets.pluck("id").serialize())
                .where("poll_choice_id", choice.id)
                .sum("total_voter")
                .first()
                .total_voter
            )
            try:
                ReportWinner.create(
                    {
                        "report_id": report.id,
                        "poll_choice_id": choice.id,
                        "total_voters": tv,
                    }
                )
                report_voters = report_voters + tv
                resume[choice.option] = tv

            except QueryException:
                continue

        report.update(
            {"total_voters": report_voters, "winner_resume": json.dumps(resume)}
        )

    def weekly(self):
        pass

    def monthly(self):
        pass

    def quarterly(self):
        pass

    def yearly(self):
        pass

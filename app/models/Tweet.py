""" Tweet Model """

from masoniteorm.models import Model
from masoniteorm.relationships import has_one, has_many


class Tweet(Model):
    """Tweet Model"""

    @has_many("poll_id")
    def polls(self):
        from .Poll import Poll

        return Poll

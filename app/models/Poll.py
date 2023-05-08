""" Poll Model """

from masoniteorm.models import Model
from masoniteorm.relationships import belongs_to

class Poll(Model):
    """Poll Model"""

    @belongs_to
    def tweet(self):
        from .Tweet import Tweet
        return Tweet

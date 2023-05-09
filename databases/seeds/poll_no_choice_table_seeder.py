"""PollNoChoiceTableSeeder Seeder."""

from masoniteorm.seeds import Seeder

from app.models import PollChoice


class PollNoChoiceTableSeeder(Seeder):
    def run(self):
        """Run the database seeds."""
        PollChoice.create({"option": "Belum ada pilihan"})

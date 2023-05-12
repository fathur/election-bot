import math
import os
import sentry_sdk

import click
import tweepy

from app.services.poll_maker import PollMaker
from app.services.reporting import Reporting


@click.group()
def run():
    pass


@run.command()
def create_poll():
    click.echo("Executing create poll")
    PollMaker.run()
    click.echo("Executed create poll")


@run.command()
@click.option(
    "-i",
    "--interval",
    required=True,
    show_default=True,
    type=click.Choice(
        ["daily", "weekly", "monthly", "quarterly", "yearly"], case_sensitive=True
    ),
)
def generate_report(interval):
    import pendulum

    click.echo(f"Executing generate report {interval}")
    Reporting.run(interval, end_at=pendulum.datetime(2023, 5, 10))
    click.echo(f"Executed generate report {interval}")


@run.command()
def test():
    d = {"apple": 15, "orange": 12, "banana": 10, "kiwi": 20}

    # Sort the dictionary by value in descending order
    sorted_d = dict(sorted(d.items(), key=lambda item: item[1], reverse=True))
    click.echo(sorted_d)
